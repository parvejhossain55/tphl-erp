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

                                   <th>Company name/Project</th>
                                   <th>phone_number</th>
                                   <th>job title</th>
                                   <th>Interested Apartment Size</th>
                                   <th>Interset Floor for Shop</th>
                                   <th>Planning To Buy Time</th>
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
                                       <td><?= htmlspecialchars($lead['company_name'] ?? 'N/A') ?></td>

                                       <!-- Phone -->
                                       <td><?= htmlspecialchars($lead['phone_number'] ?? 'N/A') ?></td>

                                       <!-- Job Title -->
                                       <td><?= htmlspecialchars($lead['job_title'] ?? 'N/A') ?></td>

                                       <!-- Apartment Size -->
                                       <td>
                                           <?= htmlspecialchars($lead['which_apartment_size_are_you_interested_in'] ?? $lead['pref_flat_size'] ?? 'N/A') ?>
                                       </td>

                                       <!-- Floor -->
                                       <td>
                                           <?= htmlspecialchars($lead['which_floor_are_you_for_shop'] ?? 'N/A') ?>
                                       </td>

                                       <!-- Planning to Buy -->
                                       <td>
                                           <?= htmlspecialchars($lead['when_are_you_planning_to_buy'] ?? 'N/A') ?>
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
                                           <textarea
                                               class="call-note"
                                               data-id="<?= $lead['id'] ?>"
                                               placeholder="Click to add note..."><?= htmlspecialchars($lead['call_note'] ?? '') ?></textarea>
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
       // after typing note auto safe 

document.querySelectorAll('.call-note').forEach(textarea => {

    let timeout = null;

    textarea.addEventListener('keyup', function () {
        clearTimeout(timeout);

        timeout = setTimeout(() => {
            saveNote(this);
        }, 800); // 800ms pause হলে save
    });

    textarea.addEventListener('blur', function () {
        saveNote(this);
    });

    function saveNote(el) {
        fetch('<?= $base_url ?>/update-call-notes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body:
                'lead_id=' + encodeURIComponent(el.dataset.id) +
                '&call_note=' + encodeURIComponent(el.value)
        })
        .then(res => res.text())
        .then(() => {
            el.style.borderColor = '#198754'; // success hint
            setTimeout(() => el.style.borderColor = '#ddd', 800);
        });
    }
});
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
    box-shadow: 0 0 0 2px rgba(13,110,253,.15);
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
   </style>