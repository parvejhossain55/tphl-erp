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
           <div class="desktop-only">
               <div class="col-xxl-12">
                   <div class="card__wrapper">
                       <div class="table__wrapper table-responsive">
                           <table class="table mb-20 ">
                               <thead>
                                   <tr class="table__title table__sort">


                                       <th style="min-width:60px;">S/L</th>
                                       <th class="th_1">Client/Lead Name</th>

                                       <!-- <th>Source Type</th> -->

                                       <th>Project name</th>
                                       <th>phone_number</th>
                                       <th>Follow Up</th>
                                       <th class="d-none">Booking</th>
                                       <th class="d-none">job title</th>
                                       <th class="d-none">Interested Apartment Size</th>
                                       <th class="d-none">Interset Floor for Shop</th>
                                       <th>Best Time to Contact</th>
                                       <th class="text-center">Call Type</th>
                                       <th class="text-center">Communicate Type</th>
                                       <th class="text-center">Lead Quality</th>
                                       <th class="text-center">Communicate Type</th>
                                       <th class="text-center">Note</th>
                                       <th class="text-center">Show</th>
                                   </tr>
                               </thead>
                               <tbody class="table__body">
                                   <?php foreach ($todayLeads as $key => $lead): ?>
                                       <tr>
                                           <!-- S/L -->
                                           <td><?= $key + 1 ?></td>

                                           <!-- Client / Lead Name -->
                                           <td><?= htmlspecialchars($lead['full_name'] ?? 'N/A') ?></td>

                                           <?php
                                            $displayName = !empty($lead['campaign_name'])
                                                ? $lead['campaign_name']
                                                : (!empty($lead['project_name']) ? $lead['project_name'] : '-');

                                            $plainText = strip_tags($displayName);
                                            ?>


                                           <td>
                                               <div class="d-flex align-items-center gap-2 flex-nowrap">
                                                   <span class="project-badge mb-0">
                                                       <i class="bi bi-building"></i>
                                                       <?= truncateText($displayName, 15) ?>
                                                   </span>

                                                   <?php if (mb_strlen($plainText) > 15): ?>
                                                       <button type="button"
                                                           id="viewCampaignBtn<?= $lead['id'] ?>"
                                                           class="btn btn-sm btn-outline-primary flex-shrink-0"
                                                           data-description="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#viewProjectCampaignName">
                                                           View



                                                       </button>
                                                   <?php endif; ?>
                                               </div>
                                           </td>

                                           <!-- Phone -->
                                           <td>
                                               <?php if (!empty($lead['phone_number'])):
                                                    $phone = preg_replace('/[^0-9+]/', '', $lead['phone_number']);
                                                ?>
                                                   <a href="tel:<?= $phone ?>" class="text-decoration-none">
                                                       <?= htmlspecialchars($phone) ?>
                                                   </a>
                                               <?php else: ?>
                                                   N/A
                                               <?php endif; ?>
                                           </td>

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

                                           <td>
                                               <a href="<?= $GLOBALS['base_url'] ?>/follow-up-leads/<?= $lead['id'] ?>"
                                                   class="btn btn-primary">

                                                   <span>Follow Up</span>
                                                   <?php if (!empty($followUpCounts[$lead['id']])): ?>
                                                       <span class="action-badge">
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

                                           <!-- Action -->

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
                                           <?php
                                            $status = $lead['communication_type'] ?? 'Not Select';

                                            $statusClass = '';
                                            if ($status == 'WA Messaging') {
                                                $statusClass = 'bg-received';
                                            } elseif ($status == 'WA Conversion') {
                                                $statusClass = 'bg-not-received';
                                            }
                                            ?>
                                           <td class="call-status-cell">
                                               <select
                                                   class="communication-status <?= $statusClass ?> form-select form-control"
                                                   data-id="<?= $lead['id'] ?>">
                                                   <option value="">Select type</option>
                                                   <option value="WA Messaging" <?= $status == 'WA Messaging' ? 'selected' : '' ?>>WA Messaging</option>
                                                   <option value="WA Conversion" <?= $status == 'WA Conversion' ? 'selected' : '' ?>>WA conversation</option>

                                               </select>
                                           </td>
                                           <?php
                                            $quality = $lead['lead_quality'] ?? 'Pending';

                                            $class = '';
                                            if ($quality == 'Prospect') {
                                                $class = 'bg-received';
                                            } elseif ($quality == 'Not Interested') {
                                                $class = 'bg-not-received';
                                            }
                                            ?>

                                           <td>
                                               <select
                                                   class="lead-quality <?= $class ?> form-control select-form"
                                                   data-id="<?= $lead['id'] ?>">
                                                   <option value="Pending" <?= $quality == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                   <option value="Prospect" <?= $quality == 'Prospect' ? 'selected' : '' ?>>Prospect</option>
                                                   <option value="Not Interested" <?= $quality == 'Not Interested' ? 'selected' : '' ?>>Not Interested</option>
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
                                           <td>
                                               <a class="table__icon download" href="<?= $GLOBALS['base_url'] ?>/view-lead/<?= $lead['id'] ?>"
                                                   title="View Transaction History"> <i class="fa-regular fa-eye"></i>
                                               </a>
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
       <div class="mobile-only">
           <?php foreach ($todayLeads as $key => $lead): ?>
               <?php
                $displayName = !empty($lead['campaign_name'])
                    ? $lead['campaign_name']
                    : (!empty($lead['project_name']) ? $lead['project_name'] : '-');
                $plainText = strip_tags($displayName);

                // Clean phone for call link
                $phone = !empty($lead['phone_number']) ? preg_replace('/[^0-9+]/', '', $lead['phone_number']) : '';
                ?>
               <div class="lead-card mb-3 p-2 border rounded" data-id="<?= $lead['id'] ?>">
                   <!-- Header: Lead Name + Phone -->
                   <div class="d-flex justify-content-between align-items-center">
                       <div class="">
                           <input type="checkbox" class="selectedIdMobile me-2 d-none" data-id="<?= $lead['id'] ?>">
                           <span class="lead-name fw-bold"><?= htmlspecialchars($lead['full_name'] ?? 'N/A') ?></span>
                       </div>
                       <div>
                           <?php if ($phone): ?>
                               <a href="tel:<?= $phone ?>" class="text-decoration-none fw-bold me-2"><?= htmlspecialchars($phone) ?></a>
                               <a href="https://wa.me/<?= $phone ?>" target="_blank" class="text-success">
                                   <i class="bi bi-whatsapp"></i>
                               </a>
                           <?php else: ?>
                               N/A
                           <?php endif; ?>
                       </div>
                   </div>

                   <!-- Status -->
                   <div class="mt-2">
                       <label class="d-block mb-1">Call Status</label>
                       <select class="call-status w-100" data-id="<?= $lead['id'] ?>">
                           <option value="not_called" <?= $lead['call_status'] == 'not_called' ? 'selected' : '' ?>>Not Called</option>
                           <option value="called" <?= $lead['call_status'] == 'called' ? 'selected' : '' ?>>Called</option>
                           <option value="received" <?= $lead['call_status'] == 'received' ? 'selected' : '' ?>>Received</option>
                           <option value="not_received" <?= $lead['call_status'] == 'not_received' ? 'selected' : '' ?>>Not Received</option>
                       </select>
                   </div>

                   <!-- Follow Up & Booking -->
                   <div class="d-flex gap-2 flex-wrap mt-2">
                       <a href="<?= $GLOBALS['base_url'] ?>/follow-up-leads/<?= $lead['id'] ?>" class="btn btn-primary btn-sm flex-grow-1 d-flex justify-content-between align-items-center">
                           <span>Follow Up</span>
                           <?php if (!empty($followUpCounts[$lead['id']])): ?>
                               <span class="badge bg-secondary"><?= $followUpCounts[$lead['id']] ?></span>
                           <?php endif; ?>
                       </a>


                   </div>

                   <!-- Project Info -->
                   <div class="project-detail mt-2">
                       <div class="info-label flex-shrink-0">Project</div>
                       <div class="d-flex align-items-center gap-2 mt-1">
                           <span class="project-badge mb-0">
                               <i class="bi bi-building"></i>
                               <?= truncateText($displayName, 15) ?>
                           </span>
                           <?php if (mb_strlen($plainText) > 15): ?>
                               <button type="button"
                                   id="viewCampaignBtn<?= $lead['id'] ?>"
                                   class="btn btn-sm btn-outline-primary ms-3 w-auto px-1 py-0"
                                   data-description="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                   data-bs-toggle="modal"
                                   data-bs-target="#viewProjectCampaignName"
                                   title="View Project">View
                               </button>
                           <?php endif; ?>
                       </div>
                   </div>

                   <!-- Next Follow-Up -->
                   <div class="mt-2">
                       <label class="d-block mb-1">Next Follow-Up</label>
                       <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1">
                           <i class="bi bi-calendar2-check me-1"></i>
                           <?= !empty($lead['next_follow_up_date']) && $lead['next_follow_up_date'] !== '0000-00-00'
                                ? formatDate($lead['next_follow_up_date'])
                                : 'No Schedule' ?>
                       </span>
                   </div>

                   <!-- Best Time to Contact -->
                   <div class="mt-2">
                       <label class="d-block mb-1">Best Time to Contact</label>
                       <span><?= htmlspecialchars($lead['when_is_the_best_time_to_contact_you'] ?? 'N/A') ?></span>
                   </div>



                   <!-- Notes -->
                   <div class="mt-2">
                       <label class="d-block mb-1">Notes</label>
                       <textarea class="form-control call-note" data-id="<?= $lead['id'] ?>" placeholder="Add note..."><?= htmlspecialchars($lead['call_note'] ?? '') ?></textarea>
                       <button type="button" class="btn btn-sm btn-success mt-1 note-save-btn" data-id="<?= $lead['id'] ?>">Save</button>
                   </div>
               </div>
           <?php endforeach; ?>
       </div>


       <!-- view full rule description -->
       <div id="viewProjectCampaignName" class="modal custom-modal fade">
           <div class="modal-dialog modal-dialog-centered modal-lg">
               <div class="modal-content">

                   <div class="modal-header">
                       <h5 class="modal-title">Project Campaing Name</h5>
                       <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                           <i class="fa-solid fa-xmark-large"></i>
                       </button>
                   </div>

                   <div class="modal-body">
                       <textarea id="view_campaign_id" class="form-control" rows="2" readonly></textarea>

                   </div>

               </div>
           </div>
       </div>
   </div>
   <script>
       document.querySelectorAll('[id^="viewCampaignBtn"]').forEach(function(btn) {
           btn.addEventListener('click', function() {
               //From data-description  full text 
               const description = this.getAttribute('data-description');
               // textarea  set 
               document.getElementById('view_campaign_id').value = description;
           });
       });
   </script>
   <script>
       const base_url = "<?= $GLOBALS['base_url'] ?>";
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
       // Communication type update
       document.querySelectorAll('.communication-status').forEach(select => {
           select.addEventListener('change', function() {
               fetch(base_url + '/update-communication-type', {
                   method: 'POST',
                   headers: {
                       'Content-Type': 'application/x-www-form-urlencoded'
                   },
                   body: 'lead_id=' + this.dataset.id + '&communication_type=' + this.value
               });
           });
       });

       // lead quality
       document.querySelectorAll('.lead-quality').forEach(select => {
           select.addEventListener('change', function() {

               fetch(base_url + '/update-lead-quality', {
                       method: 'POST',
                       headers: {
                           'Content-Type': 'application/x-www-form-urlencoded'
                       },
                       body: 'lead_id=' + this.dataset.id + '&lead_quality=' + encodeURIComponent(this.value)
                   })
                   .then(res => res.json())
                   .then(data => {
                       console.log(data);
                   })
                   .catch(err => {
                       console.error(err);
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