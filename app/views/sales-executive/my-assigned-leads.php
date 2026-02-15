   <?php
    $page_title = "My All Assigned Lead";

    ?>

   <div class="row">
       <div class="card__wrapper">

           <div class="col-12">
               <h4 class="mb-0">All Assigned Leads</h4>
           </div>
       </div>

       <form action="" method="get">
           <div class="row  mb-3 align-items-end">

               <?php
                $currentYear = date('Y');
                $startYear   = $currentYear - 10;
                $selectedYear = $slectedYear ?? '';
                ?>
               <!-- Year Select -->
               <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-12">
                   <div class="card__wrapper h-100">
                       <div class="from__input-box">
                           <select class="form-control" name="year">
                               <option value="">Year Select</option>

                               <?php for ($year = $currentYear; $year >= $startYear; $year--): ?>
                                   <option
                                       value="<?= $year ?>"
                                       <?= $selectedYear == $year ? 'selected' : '' ?>>
                                       <?= $year ?>
                                   </option>
                               <?php endfor; ?>

                           </select>
                       </div>
                   </div>
               </div>

               <?php
                $months = [
                    1 => 'January',
                    2 => 'February',
                    3 => 'March',
                    4 => 'April',
                    5 => 'May',
                    6 => 'June',
                    7 => 'July',
                    8 => 'August',
                    9 => 'September',
                    10 => 'October',
                    11 => 'November',
                    12 => 'December'
                ];
                $selectedMonth = $_GET['month'] ?? '';
                ?>

               <!-- Month Select -->
               <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-12">
                   <div class="card__wrapper h-100">
                       <div class="from__input-box">
                           <select class="form-control" name="month">
                               <option value="">Month Select</option>
                               <?php foreach ($months as $id => $name): ?>

                                   <option value="<?= $id ?>" <?= $selectedMonth == $id ? 'selected' : '' ?>>
                                       <?= $name ?>
                                   </option>
                               <?php endforeach; ?>
                           </select>

                       </div>
                   </div>
               </div>

               <!-- Buttons -->
               <div class="col-xxl-4 col-xl-12 col-lg-12 col-md-12 col-12">
                   <div class="card__wrapper h-100">
                       <div class="d-flex flex-row gap-2 align-items-center p-0">
                           <button type="submit" class="btn btn-secondary btn-sm flex-grow-1 py-1">
                               Search
                           </button>

                           <a href="<?= $GLOBALS['base_url'] ?>/my-all-assigned-leads"
                               class="btn btn-success btn-sm py-1 flex-grow-1">
                               <i class="fa-light fa-file-arrow-up me-1"></i>
                               Reset
                           </a>



                           <a class="btn btn-primary btn-sm flex-grow-1 py-1"
                               href="<?= $GLOBALS['base_url'] ?>/create-lead">
                               All Follow Up Leads
                           </a>
                       </div>
                   </div>
               </div>
           </div>
       </form>

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
                                   <th>Booking</th>
                                   <th class="d-none">job title</th>
                                   <th class="d-none">Interested Apartment Size</th>
                                   <th class="d-none">Interset Floor for Shop</th>
                                   <th class="d-none">Planning To Buy Time</th>
                                   <th>Best Time to Contact</th>
                                   <th>city</th>
                                   <th class="">Last Follow Up Action</th>
                                   <th class="text-center">Action</th>
                               </tr>
                           </thead>
                           <tbody class="table__body">
                               <?php foreach ($myAllleads as $key => $lead): ?>
                                   <tr>
                                       <!-- S/L -->
                                       <td><?= $key + 1 ?></td>

                                       <!-- Client / Lead Name -->
                                       <td><?= htmlspecialchars($lead['full_name'] ?? 'N/A') ?></td>

                                       <!-- Company / Project -->
                                       <td><?= htmlspecialchars($lead['company_name'] ?? 'N/A') ?></td>

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

                                       <td>
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
                                


                                       <td>

                                           <span class="bd-badge bg-primary fw-semibold fs-6">
                                               <?= $lead['next_action'] ?? 'N/A'?>
                                           </span>
                                       </td>
                                       <td>
                                           <!-- New Last Action Update Button -->
                                           <form method="POST"
                                               action="<?= $GLOBALS['base_url'] ?>/update-last_leadAction/<?= $lead['id'] ?>"
                                               class="m-0 p-0">

                                               <select name="last_lead_action"
                                                   class="form-select form-select-sm last-action-select <?= 'action-' . ($lead['last_lead_action'] ?? 'not_called') ?>"
                                                   onchange="this.form.submit()">

                                                   <option value=""> Update action </option>
                                                   <option value="not_called" <?= $lead['last_lead_action'] == 'not_called' ? 'selected' : '' ?>>
                                                       Not Called
                                                   </option>

                                                   <option value="booked" <?= $lead['last_lead_action'] == 'booked' ? 'selected' : '' ?>>
                                                       Booked
                                                   </option>

                                                   <option value="called" <?= $lead['last_lead_action'] == 'called' ? 'selected' : '' ?>>
                                                       Called
                                                   </option>

                                                   <option value="visited" <?= $lead['last_lead_action'] == 'visited' ? 'selected' : '' ?>>
                                                       Visited
                                                   </option>
                                                   <option value="canceled" <?= $lead['last_lead_action'] == 'canceled' ? 'selected' : '' ?>>
                                                       Canceled
                                                   </option>
                                               </select>

                                           </form>

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
   <style>
       /* Note column width */
       .note-cell {
           min-width: 220px;
           max-width: 260px;
           position: relative;
       }

       /* Default small note */
       .call-note {
           width: 100%;
           height: 32px;
           resize: none;
           overflow: hidden;
           padding: 6px 8px;
           font-size: 13px;
           border-radius: 6px;
           border: 1px solid #ced4da;
           transition: all 0.25s ease;
           cursor: pointer;
           background-color: #f9f9f9;
       }

       /* Show text nicely */
       .call-note:not(:focus) {
           white-space: nowrap;
           text-overflow: ellipsis;
       }

       /* On focus expand */
       .call-note:focus {
           position: absolute;
           z-index: 999;
           height: 140px;
           width: 360px;
           background: #fff;
           box-shadow: 0 10px 30px rgba(0, 0, 0, .15);
           cursor: text;
       }

       /* Optional: highlight when has content */
       .call-note:not(:placeholder-shown) {
           border-color: #0d6efd;
           background: #f0f6ff;
       }



       /* status update   */
       .call-status-cell {
           min-width: 130px;
       }

       /* Base dropdown */
       .call-status {
           height: 32px;
           padding: 0 28px 0 10px;
           font-size: 12px;
           font-weight: 600;
           border-radius: 999px;
           border: 1px solid #ddd;
           background-color: #f8f9fa;
           cursor: pointer;
           transition: all .2s ease;
       }

       /* Hover */
       .call-status:hover {
           border-color: #0d6efd;
       }

       /* Focus */
       .call-status:focus {
           outline: none;
           box-shadow: 0 0 0 2px rgba(13, 110, 253, .15);
       }

       /* Status colors */
       .call-status[value="not_called"],
       .call-status option[value="not_called"]:checked {
           background-color: #f1f3f5;
           color: #495057;
       }

       .call-status[value="called"],
       .call-status option[value="called"]:checked {
           background-color: #fff3cd;
           color: #856404;
       }

       .call-status[value="received"],
       .call-status option[value="received"]:checked {
           background-color: #d1e7dd;
           color: #0f5132;
       }

       .call-status[value="not_received"],
       .call-status option[value="not_received"]:checked {
           background-color: #f8d7da;
           color: #842029;
       }

       .note-cell .note-wrapper {
           display: flex;
           gap: 5px;
           /* textarea & button gap */
           align-items: center;
       }

       .note-cell .call-note {
           flex: 1;
           /* textarea width auto adjust */
           height: 32px;
       }

       .note-cell .note-save-btn {
           height: 32px;
           padding: 0 8px;
           font-size: 12px;
       }
   </style>