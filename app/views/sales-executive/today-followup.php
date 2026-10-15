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

                         <th class="text-center">Action</th>
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
                                 <td class="text-center">
                            <!-- Follow Up Action -->
                            <button type="button" class="btn btn-success btn-sm position-relative follow-up-btn create-followup-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#createFollowUpModal"
                                    data-lead-id="<?= $followUp['lead_id'] ?>"
                                    style="white-space:nowrap; padding: 0.25rem 0.5rem;">
                                <i class="bi bi-calendar-plus"></i>
                                Follow Up
                                <?php if (!empty($followUpCounts[$followUp['lead_id']])): ?>
                                    <span class="action-badge" style="">
                                        <?= $followUpCounts[$followUp['lead_id']] ?>
                                    </span>
                                <?php endif; ?>
                            </button>

                            <!-- Follow Up History -->
                            <button type="button" class="d-none btn btn-info btn-sm position-relative ms-1 view-history-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#followupHistoryModal"
                                    data-lead-id="<?= $followUp['lead_id'] ?>"
                                    data-history="<?= htmlspecialchars(json_encode($followUpHistories[$followUp['lead_id']] ?? []), ENT_QUOTES, 'UTF-8') ?>"
                                    style="white-space:nowrap; padding: 0.25rem 0.5rem; color: #fff;">
                                <i class="bi bi-clock-history"></i> History
                            </button>
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

 

<!-- Follow Up History Modal -->
<div class="modal custom-modal fade" id="followupHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-list-task me-2"></i> Follow Up History</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="white-space: nowrap;">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Lead</th>
                                <th>Project</th>
                                <th>Flat</th>
                                <th>Follow Up Date</th>
                                <th>Follow Up Time</th>
                                <th>Type</th>
                                <th>Discussion Note</th>
                                <th>Next Follow Up</th>
                                <th>Next Action</th>
                                <th class="text-center" style="min-width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="followUpHistoryBody">
                            <tr>
                                <td colspan="11" class="text-center text-muted">Loading history...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Follow Up Modal -->
<div class="modal custom-modal fade" id="createFollowUpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createFollowUpModalTitle"><i class="bi bi-calendar-plus me-2"></i> Create Follow Up</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Edit mode notification banner -->
                <div id="followUpEditModeAlert" class="alert alert-warning py-2 px-3 mb-3 align-items-center justify-content-between" style="display: none;">
                    <div>
                        <i class="fa-solid fa-pen-to-square me-2 text-warning-emphasis"></i>
                        <strong>Editing Follow Up #<span id="followUpEditIdDisplay"></span></strong>
                        <span class="text-muted ms-2 fs-12">(Created by: <span id="followUpEditCreatorDisplay"></span>)</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-dark py-0 px-2" id="cancelFollowUpEditBtn">
                        <i class="fa-solid fa-xmark me-1"></i> Cancel Edit
                    </button>
                </div>

                <form id="createFollowUpForm" action="<?= $GLOBALS['base_url'] ?>/store-follow-up" method="POST">
                    <input type="hidden" name="lead_id" id="createFollowUpLeadId" value="">
                    <input type="hidden" name="id" id="createFollowUpEditId" value="">
                    <input type="hidden" name="is_ajax" value="1">
                    <input type="hidden" name="assigned_with" value="<?= $_SESSION['user']['id'] ?? 1 ?>">
                    <input type="hidden" name="created_by" value="<?= $_SESSION['user_id'] ?? 1 ?>">

                    <div class="row gy-3">
                        <!-- Project -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Project</label>
                            <select name="project_id" id="createFollowUpProjectSelect" class="form-select select2-no-scroll" data-placeholder="Select Project">
                                <option value="">Select Project</option>
                                <?php foreach ($projects as $project): ?>
                                    <?php 
                                        $isRestricted = in_array($project['project_sale_status'], ['Sold Out', 'Upcoming']);
                                        $statusSuffix = !empty($project['project_sale_status']) ? " ({$project['project_sale_status']})" : '';
                                    ?>
                                    <option value="<?= $project['id'] ?>" <?= $isRestricted ? 'disabled style="color: red;"' : '' ?>>
                                        <?= htmlspecialchars($project['name']) ?><?= $statusSuffix ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Flat / Unit -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Flat / Unit</label>
                            <select id="createFollowUpFlatSelect" name="flat_id" class="form-select select2-no-scroll" data-placeholder="Select Flat">
                                <option value="">Select Flat</option>
                            </select>
                        </div>

                        <!-- Follow Up Date -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Follow Up Date*</label>
                            <input type="date" name="follow_up_date" class="form-control" required>
                        </div>

                        <!-- Follow Up Time -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Follow Up Time</label>
                            <input type="time" name="follow_up_time" class="form-control">
                        </div>

                        <!-- Follow Up Type -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Follow Up Type</label>
                            <select name="follow_up_type" class="form-select">
                                <option value="">Select Type</option>
                                <option value="Inbound Call">Inbound Call</option>
                                <option value="Phone Call">Phone Call</option>
                                <option value="WA Call">WA Call</option>
                                <option value="Imo Call">Imo Call</option>
                                <option value="Messenger Call">Messenger Call</option>
                                <option value="Messenger Conversesion">Messenger Conversesion</option>
                                <option value="WA Conversesion">WA Conversesion</option>
                                <option value="WhatsApp Message">WhatsApp Message</option>
                                <option value="Imo Message">Imo Message</option>
                                <option value="Phone Massaging">Phone Massaging</option>
                                <option value="Email">Email</option>
                                <option value="Number Off">Number Off</option>
                                <option value="Wrong Number">Wrong Number</option>
                                <option value="Invalid Number">Invalid Number</option>
                                <option value="Face To Face">Face To Face</option>
                                <option value="call">Call (Legacy)</option>
                                <option value="visit">Visit (Legacy)</option>
                                <option value="meeting">Meeting (Legacy)</option>
                                <option value="whatsapp">WhatsApp (Legacy)</option>
                                <option value="Email">Email (Legacy)</option>
                            </select>
                        </div>

                        <!-- Lead Status -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Lead Status</label>
                            <select name="lead_status" id="createModalLeadStatus" class="form-select">
                                <option value="">Select Status</option>
                                <?php
                                $statuses = [
                                    'No Response', 'Pending', 'Prospect', 'High Prospect', 
                                    'Will Visit', 'Visited', 'Sold', 'Lead Transferred', 
                                    'Project Transferred', 'Not Interested', 'Don\'t Call Again', 
                                    'Refund', 'Future', 'Cold', 'Hot', 'Warm', 'Irrelevant'
                                ];
                                foreach($statuses as $st) {
                                    echo "<option value=\"$st\">$st</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <!-- Team Re-assign (Hidden by default) -->
                        <div class="col-lg-6" id="createModalReassignTeamDiv" style="display: none;">
                            <label class="form__input-title">Re-assigned to (Team)</label>
                            <select name="team_id" id="createModalReassignTeamId" class="form-select">
                                <option value="">Select Team</option>
                                <?php foreach (($teams ?? []) as $team): ?>
                                    <option value="<?= $team['id'] ?>"><?= htmlspecialchars($team['name'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Follow Up Status -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Follow Up Status</label>
                            <select name="status" class="form-select">
                                <option value="">Select Status</option>
                                <option value="pending" selected>Pending</option>
                                <option value="completed">Completed</option>
                                <option value="missed">Missed</option>
                                <option value="canceled">Canceled</option>
                            </select>
                        </div>

                        <!-- Follow Up Agent (Optional) -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Follow-up Agent (Optional)</label>
                            <select name="follow_up_agent_id" class="form-select">
                                <option value="">Myself (Default)</option>
                                <?php foreach (($teamMembers ?? []) as $member): ?>
                                    <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['full_name'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Discussion Note -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Discussion Note*</label>
                            <textarea name="discussion_note" class="form-control" rows="3" required></textarea>
                        </div>

                        <!-- Next Follow Up Date -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Next Follow Up Date</label>
                            <input type="date" name="next_follow_up_date" class="form-control">
                        </div>

                        <!-- Next Action -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Next Action</label>
                            <select name="next_action" class="form-select">
                                <option value="">Select Action</option>
                                <option value="site visit">Site Visit</option>
                                <option value="booking">Booking</option>
                                <option value="call_back">Call Back</option>
                                <option value="take_time">Take Time</option>
                            </select>
                        </div>

                        <!-- Next Follow Up Plan Notes -->
                        <div class="col-lg-6 mt-2">
                            <label class="form__input-title">Next Follow Up Plan Notes</label>
                            <textarea name="next_follow_up_plan_notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveFollowUpBtn">Save Follow Up</button>
                    </div>
                </form>

                <hr class="my-4">
                
                <h6 class="mb-3"><i class="bi bi-clock-history me-2"></i>Recent Follow Ups</h6>
                <div class="table-responsive border" style="max-height: 250px; overflow-y: auto;">
                    <table class="table table-bordered table-striped table-sm mb-0" style="font-size: 0.85rem; white-space: nowrap;">
                        <thead class="bg-light sticky-top" style="z-index: 1; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                            <tr>
                                <th>#</th>
                                <th>Lead</th>
                                <th>Project</th>
                                <th>Flat</th>
                                <th>Follow Up Date</th>
                                <th>Follow Up Time</th>
                                <th>Type</th>
                                <th>Lead Status</th>
                                <th>Follow Up Status</th>
                                <th>Discussion Note</th>
                                <th>Next Follow Up</th>
                                <th>Next Action</th>
                                <th>Follow Up With</th>
                                <th class="text-center" style="min-width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="createModalHistoryBody">
                            <tr>
                                <td colspan="14" class="text-center text-muted">Loading history...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentUserId = <?= (int)($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0) ?>;
    const currentUserRole = <?= (int)($_SESSION['user']['role_id'] ?? 0) ?>;
    let modalHistoryData = [];

    function escapeHtml(text) {
        if (!text) return '';
        return text.toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function loadFlatsForProject(projectId, selectedFlatId = null) {
        let flatSel = $('#createFollowUpFlatSelect');
        if (projectId) {
            flatSel.html('<option value="">Loading...</option>');
            fetch(`<?= $GLOBALS['base_url'] ?>/get-flats/${projectId}`)
                .then(res => res.json())
                .then(data => {
                    flatSel.html('<option value="">Select Flat</option>');
                    data.forEach(flat => {
                        const flatId = flat.flat_id ?? flat.id;
                        if (!flatId) return;
                        
                        let option = document.createElement('option');
                        option.value = flatId;
                        if (flat.is_booked == 1 && (!selectedFlatId || flatId != selectedFlatId)) {
                            option.textContent = `${flat.flat_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft [Booked]`;
                            option.classList.add('booked-option');
                            option.disabled = true;
                        } else {
                            option.textContent = `${flat.flat_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft`;
                        }
                        if (selectedFlatId && Number(flatId) === Number(selectedFlatId)) {
                            option.selected = true;
                        }
                        flatSel.append(option);
                    });
                    if (selectedFlatId) {
                        flatSel.val(selectedFlatId);
                    }
                    if (flatSel.hasClass('select2-hidden-accessible')) {
                        flatSel.trigger('change.select2');
                    }
                })
                .catch(err => {
                    console.error('Failed to load flats', err);
                    flatSel.html('<option value="">Error loading flats</option>');
                });
        } else {
            flatSel.html('<option value="">Select Flat</option>');
        }
    }

    function resetFormToCreateMode() {
        $('#createFollowUpForm').attr('action', '<?= $GLOBALS['base_url'] ?>/store-follow-up');
        $('#createFollowUpEditId').val('');
        $('#followUpEditModeAlert').hide().removeClass('d-flex').addClass('d-none');
        $('#createFollowUpModalTitle').html('<i class="bi bi-calendar-plus me-2"></i> Create Follow Up');
        $('#saveFollowUpBtn').removeClass('btn-warning').addClass('btn-primary').html('Save Follow Up');
        
        let leadId = $('#createFollowUpLeadId').val();
        $('#createFollowUpForm')[0].reset();
        $('#createFollowUpLeadId').val(leadId);
        $('#createModalReassignTeamDiv').hide();
        $('#createFollowUpFlatSelect').html('<option value="">Select Flat</option>');
        $('#createFollowUpProjectSelect').val('');
        if ($('#createFollowUpProjectSelect').hasClass('select2-hidden-accessible')) {
            $('#createFollowUpProjectSelect').trigger('change.select2');
        }
    }

    function loadModalHistory(leadId) {
        let tbody = $('#createModalHistoryBody');
        tbody.html('<tr><td colspan="12" class="text-center text-muted">Loading history...</td></tr>');
        modalHistoryData = [];
        
        fetch(`<?= $GLOBALS['base_url'] ?>/get-follow-up-history/${leadId}`)
            .then(res => res.json())
            .then(data => {
                let html = '';
                if (Array.isArray(data) && data.length > 0) {
                    modalHistoryData = data;
                    data.forEach(item => {
                        const canEdit = (currentUserId && Number(item.created_by) === Number(currentUserId)) || (currentUserRole === 1);
                        let actionHtml = '';
                        if (canEdit) {
                            actionHtml = `<button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 edit-modal-followup-btn" data-fuid="${item.id}" title="Edit this follow up">
                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                            </button>`;
                        } else {
                            actionHtml = `<span class="badge bg-light text-muted border py-1 px-2" title="Only the creator can edit this follow up"><i class="fa-solid fa-lock me-1"></i> No Access</span>`;
                        }

                        let lsBadge = '<span class="text-muted">—</span>';
                        if (item.lead_status) {
                            let lsCls = 'bg-secondary text-white';
                            if (item.lead_status === 'Prospect' || item.lead_status === 'High Prospect') lsCls = 'bg-success text-white';
                            else if (item.lead_status === 'Hot') lsCls = 'bg-danger text-white';
                            else if (item.lead_status === 'Warm') lsCls = 'bg-warning text-dark';
                            else if (item.lead_status === 'Irrelevant') lsCls = 'bg-dark text-white';
                            else if (item.lead_status === 'Cold' || item.lead_status === 'Not Interested' || item.lead_status === "Don't Call Again") lsCls = 'bg-secondary text-white';
                            lsBadge = `<span class="badge ${lsCls} px-2 py-1">${escapeHtml(item.lead_status)}</span>`;
                        }

                        let fuStatusBadge = '<span class="badge bg-warning text-dark">Pending</span>';
                        if (item.status === 'completed') fuStatusBadge = '<span class="badge bg-success text-white">Completed</span>';
                        else if (item.status === 'missed') fuStatusBadge = '<span class="badge bg-danger text-white">Missed</span>';
                        else if (item.status === 'canceled') fuStatusBadge = '<span class="badge bg-secondary text-white">Canceled</span>';

                        html += `<tr>
                            <td>${item.id || ''}</td>
                            <td>${escapeHtml(item.lead_name || '')}</td>
                            <td>${escapeHtml(item.project_name || '')}</td>
                            <td>${escapeHtml(item.flat_name || '')}</td>
                            <td>${item.follow_up_date || ''}</td>
                            <td>${item.follow_up_time || ''}</td>
                            <td>${escapeHtml(item.follow_up_type || '')}</td>
                            <td>${lsBadge}</td>
                            <td>${fuStatusBadge}</td>
                            <td title="${escapeHtml(item.discussion_note || '')}">${escapeHtml(item.discussion_note || '')}</td>
                            <td>${item.next_follow_up_date || ''}</td>
                            <td>${escapeHtml(item.next_action || '')}</td>
                            <td>${escapeHtml(item.agent_name || '')}</td>
                            <td class="text-center">${actionHtml}</td>
                        </tr>`;
                    });
                    
                    // Update the badge count on the main page silently
                    let badge = $(`.create-followup-btn[data-lead-id="${leadId}"] .action-badge`);
                    if(badge.length > 0) {
                        badge.text(data.length);
                    } else {
                        $(`.create-followup-btn[data-lead-id="${leadId}"]`).append(`<span class="action-badge">${data.length}</span>`);
                    }
                } else {
                    html = '<tr><td colspan="14" class="text-center text-muted">No history found.</td></tr>';
                }
                tbody.html(html);
            })
            .catch(err => {
                console.error("Failed to load history", err);
                tbody.html('<tr><td colspan="12" class="text-center text-danger">Failed to load history</td></tr>');
            });
    }

    // Inject lead ID when opening the Create Follow Up Modal
    $('#createFollowUpModal').on('show.bs.modal', function (event) {
        let button = $(event.relatedTarget);
        let leadId = button.attr('data-lead-id') || $('#createFollowUpLeadId').val();
        $('#createFollowUpLeadId').val(leadId);
        
        resetFormToCreateMode();
        loadModalHistory(leadId);
    });

    // Handle project change to load flats
    $('#createFollowUpProjectSelect').on('change', function() {
        let projectId = $(this).val();
        loadFlatsForProject(projectId, null);
    });

    // Handle Lead Transferred toggle
    $('#createModalLeadStatus').on('change', function() {
        if (this.value === 'Lead Transferred') {
            $('#createModalReassignTeamDiv').show();
            $('#createModalReassignTeamId').prop('required', false);
        } else {
            $('#createModalReassignTeamDiv').hide();
            $('#createModalReassignTeamId').prop('required', false);
        }
    });

    // Edit button click in modal history table
    $(document).on('click', '.edit-modal-followup-btn', function() {
        const fuid = $(this).data('fuid');
        const item = modalHistoryData.find(x => Number(x.id) === Number(fuid));
        if (!item) return;

        // Switch form to Edit mode
        $('#createFollowUpForm').attr('action', '<?= $GLOBALS['base_url'] ?>/update-follow-up');
        $('#createFollowUpEditId').val(item.id);
        $('#followUpEditIdDisplay').text(item.id);
        $('#followUpEditCreatorDisplay').text(item.agent_name || 'You');
        $('#followUpEditModeAlert').removeClass('d-none').addClass('d-flex').show();
        $('#createFollowUpModalTitle').html('<i class="fa-solid fa-pen-to-square me-2 text-warning"></i> Edit Follow Up #' + item.id);
        $('#saveFollowUpBtn').removeClass('btn-primary').addClass('btn-warning').html('<i class="fa-solid fa-check me-1"></i> Update Follow Up');

        // Populate fields
        $('#createFollowUpProjectSelect').val(item.project_id || '');
        if ($('#createFollowUpProjectSelect').hasClass('select2-hidden-accessible')) {
            $('#createFollowUpProjectSelect').trigger('change.select2');
        }
        loadFlatsForProject(item.project_id, item.flat_id);

        let fDate = item.follow_up_date ? item.follow_up_date.split(' ')[0] : '';
        let fTime = item.follow_up_time ? item.follow_up_time.substring(0, 5) : '';
        let nextDate = (item.next_follow_up_date && item.next_follow_up_date !== '0000-00-00') ? item.next_follow_up_date.split(' ')[0] : '';

        $('#createFollowUpForm input[name="follow_up_date"]').val(fDate);
        $('#createFollowUpForm input[name="follow_up_time"]').val(fTime);
        $('#createFollowUpForm select[name="follow_up_type"]').val(item.follow_up_type || '');
        $('#createModalLeadStatus').val(item.lead_status || '');

        if (item.lead_status === 'Lead Transferred') {
            $('#createModalReassignTeamDiv').show();
            $('#createModalReassignTeamId').prop('required', false).val(item.team_id || '');
        } else {
            $('#createModalReassignTeamDiv').hide();
            $('#createModalReassignTeamId').prop('required', false).val('');
        }

        $('#createFollowUpForm select[name="status"]').val(item.status || 'pending');
        $('#createFollowUpForm select[name="follow_up_agent_id"]').val(item.follow_up_agent_id || '');
        $('#createFollowUpForm textarea[name="discussion_note"]').val(item.discussion_note || '');
        $('#createFollowUpForm input[name="next_follow_up_date"]').val(nextDate);
        $('#createFollowUpForm select[name="next_action"]').val(item.next_action || '');
        $('#createFollowUpForm textarea[name="next_follow_up_plan_notes"]').val(item.next_follow_up_plan_notes || '');

        // Scroll to form top inside modal
        $('#createFollowUpModal .modal-body').animate({ scrollTop: 0 }, 300);
    });

    // Cancel edit button click
    $(document).on('click', '#cancelFollowUpEditBtn', function() {
        resetFormToCreateMode();
    });

    // Handle AJAX form submission (Create or Update)
    $('#createFollowUpForm').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
        let submitBtn = $('#saveFollowUpBtn');
        let isEditMode = Boolean($('#createFollowUpEditId').val());
        let originalHtml = submitBtn.html();
        let leadId = $('#createFollowUpLeadId').val();
        
        submitBtn.prop('disabled', true).html(isEditMode ? '<i class="fa-solid fa-spinner fa-spin me-1"></i> Updating...' : '<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');
        
        $.ajax({
            url: form.attr('action'),
            method: form.attr('method'),
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                submitBtn.prop('disabled', false).html(originalHtml);
                if (response.status === 'success') {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            toast: true,
                            position: 'top-end',
                            timer: 3000,
                            showConfirmButton: false
                        });
                    } else {
                        alert(response.message);
                    }
                    
                    resetFormToCreateMode();
                    loadModalHistory(leadId);
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Something went wrong.'
                        });
                    } else {
                        alert(response.message || 'Error occurred.');
                    }
                }
            },
            error: function(xhr, status, error) {
                submitBtn.prop('disabled', false).html(originalHtml);
                console.error('AJAX Error:', error, xhr.responseText);
                let errMsg = 'Failed to submit follow up.';
                try {
                    let jsonResp = JSON.parse(xhr.responseText);
                    if (jsonResp && jsonResp.message) errMsg = jsonResp.message;
                } catch(e) {}
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errMsg
                    });
                } else {
                    alert(errMsg);
                }
            }
        });
    });

    // History Modal Show handler
    $('#followupHistoryModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const tbody = $('#followUpHistoryBody');
        let historyDataRaw = button.attr('data-history');
        let historyData = [];
        if (historyDataRaw) {
            try { historyData = JSON.parse(historyDataRaw); } catch(e) { console.error("Failed to parse history JSON", e); }
        }
        
        let html = '';
        if (Array.isArray(historyData) && historyData.length > 0) {
            const currentLeadId = button.attr('data-lead-id');
            historyData.forEach(function(item, index) {
                const canEdit = (currentUserId && Number(item.created_by) === Number(currentUserId)) || (currentUserRole === 1);
                let actionHtml = '';
                if (canEdit) {
                    actionHtml = `<button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 history-modal-edit-btn" data-fuid="${item.id}" data-lead-id="${item.lead_id || currentLeadId}" title="Edit this follow up">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                    </button>`;
                } else {
                    actionHtml = `<span class="badge bg-light text-muted border py-1 px-2" title="Only creator can edit"><i class="fa-solid fa-lock me-1"></i> No Access</span>`;
                }

                html += `<tr>
                    <td>${index + 1}</td>
                    <td>${escapeHtml(item.full_name || '')}</td>
                    <td>${escapeHtml(item.project_name || '')}</td>
                    <td>${escapeHtml(item.flat_name || '')}</td>
                    <td>${item.follow_up_date || ''}</td>
                    <td>${item.follow_up_time || ''}</td>
                    <td>${escapeHtml(item.follow_up_type || '')}</td>
                    <td>${escapeHtml(item.discussion_note || '')}</td>
                    <td>${item.next_follow_up_date || ''}</td>
                    <td>${escapeHtml(item.next_action || '')}</td>
                    <td class="text-center">${actionHtml}</td>
                </tr>`;
            });
        } else {
            html = '<tr><td colspan="11" class="text-center text-danger">No follow-ups found for this lead.</td></tr>';
        }
        tbody.html(html);
    });

    // Edit button click inside Follow Up History Modal
    $(document).on('click', '.history-modal-edit-btn', function() {
        const fuid = $(this).data('fuid');
        const leadId = $(this).data('lead-id');
        $('#followupHistoryModal').modal('hide');
        setTimeout(function() {
            $('#createFollowUpLeadId').val(leadId);
            $('#createFollowUpModal').modal('show');
            let attempts = 0;
            let checkInterval = setInterval(function() {
                attempts++;
                let btn = $(`.edit-modal-followup-btn[data-fuid="${fuid}"]`);
                if (btn.length > 0) {
                    clearInterval(checkInterval);
                    btn.trigger('click');
                    return;
                }
                if (attempts > 30) clearInterval(checkInterval);
            }, 100);
        }, 400);
    });
});
</script>
