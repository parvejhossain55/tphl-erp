<?php
$page_title = "Follow Ups Page";
$page = 'Follow Ups';
$show_breadcrumb = true;
?>
<div class="row">

    <!-- Summary Cards -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon"><span><i class="fa-light fa-calendar-check"></i></span></div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Follow Ups</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= count($followUpLeads) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Create Follow Up Form (Always Visible) -->
<div class="row mb-4">
    <div class="col-xxl-12">
        <div class="card__wrapper p-3">
            <h5>Create Follow Up</h5>
            <form action="<?= $GLOBALS['base_url'] ?>/store-follow-up" method="POST">
                <div class="row gy-3">
                    <input type="hidden" name="lead_id" value="<?= $selected_lead_id ?? '' ?>">
                  

                    <!-- Project -->
                    <div class="col-lg-6">
                        <label class="form__input-title">Project</label>
                        <select name="project_id" id="projectSelect" class="form-select select2-no-scroll" data-placeholder="Select Project">
                            <option value="">Select Project</option>
                            <?php foreach ($projects as $project): ?>
                                <?php 
                                    $isRestricted = in_array($project['project_sale_status'], ['Sold Out', 'Upcoming']);
                                    $statusSuffix = !empty($project['project_sale_status']) ? " ({$project['project_sale_status']})" : '';
                                ?>
                                <option value="<?= $project['id'] ?>" 
                                    <?= $isRestricted ? 'disabled style="color: red;"' : '' ?>>
                                    <?= htmlspecialchars($project['name']) ?><?= $statusSuffix ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Flat -->
                    <!-- <div class="col-lg-6">
                        <label class="form__input-title">Flat</label>
                        <select name="flat_id" id="flatSelect" class="form-select">
                            <option value="">Select Flat</option>
                        </select>
                    </div> -->
                    <!-- Flat -->
                    <div class="col-lg-6">
                        <label class="form__input-title">Flat / Unit</label>
                        <select id="flatSelect" name="flat_id" class="form-select select2-no-scroll" data-placeholder="Select Flat">

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
                        <select name="lead_status" id="create_lead_status" class="form-select">
                            <option value="">Select Status</option>
                            <?php
                            $statuses = [
                                'No Response', 'Will Visit', 'Visited', 'Lead Transferred', 
                                'Not Interested', 'Don\'t Call Again', 'Cold', 'Hot', 'Warm', 'Irrelevant'
                            ];
                            foreach($statuses as $st) {
                                echo "<option value=\"$st\">$st</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Team Re-assign (Hidden by default) -->
                    <div class="col-lg-6" id="reassign_team_div" style="display: none;">
                        <label class="form__input-title">Re-assigned to (Team)</label>
                        <select name="team_id" id="reassign_team_id" class="form-select">
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
                            <option value="pending">Pending</option>
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
                                <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['full_name']) ?></option>
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
                <div class="mt-3">
                    <input type="hidden" name="assigned_with" value="<?= $_SESSION['user']['id'] ?? 1 ?>">
                    <input type="hidden" name="created_by" value="<?= $_SESSION['user_id'] ?? 1 ?>">
                    <button type="submit" class="btn btn-primary">Save Follow Up</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Follow Ups Table -->
<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title table__sort">
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
                            <th class="">Follow Up With</th>
                            <th class="d-none">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <!-- <php print_r($followUpLeads); ?> -->
                        <?php foreach ($followUpLeads as $fu): ?>


                            <tr>
                                <td><?= $fu['id'] ?></td>
                                <td><?= $fu['full_name'] ?? '' ?></td>
                                <td><?= $fu['project_name'] ?? '' ?></td>
                                <td><?= $fu['flat_name'] ?? '' ?></td>
                                <td><?= $fu['follow_up_date'] ? date('d F, Y', strtotime($fu['follow_up_date'])) : '' ?></td>
                                <td><?= $fu['follow_up_time'] ?? '' ?></td>
                                <td><?= ucfirst($fu['follow_up_type']) ?></td>
                                <td><?= $fu['discussion_note'] ?? '' ?></td>
                                <td><?= $fu['next_follow_up_date'] ?? '' ?></td>
                                <td><?= $fu['next_action'] ?? '' ?></td>
                                <td class=""><?= $fu['assigned_user'] ?? '' ?></td>
                                <td class="d-none">
                                    <div class="d-flex align-items-center justify-content-start gap-10">
                                        <a class="table__icon download" href="<?= $GLOBALS['base_url'] ?>/view-follow-up/<?= $fu['id'] ?>">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>
                                        <button
                                            class="table__icon edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#followUpModalEdit"
                                            data-id="<?= $fu['id'] ?>"
                                            data-lead-id="<?= $fu['lead_id'] ?>"
                                            data-project-id="<?= $fu['project_id'] ?>"
                                            data-flat-id="<?= $fu['flat_id'] ?>"
                                            data-date="<?= $fu['follow_up_date'] ?>"
                                            data-time="<?= $fu['follow_up_time'] ?>"
                                            data-type="<?= $fu['follow_up_type'] ?>"
                                            data-note="<?= htmlspecialchars($fu['discussion_note']) ?>"
                                            data-next-date="<?= $fu['next_follow_up_date'] ?>"
                                            data-next-action="<?= $fu['next_action'] ?>"
                                            data-lead-status="<?= $fu['lead_status'] ?? '' ?>"
                                            data-agent-id="<?= $fu['follow_up_agent_id'] ?? '' ?>"
                                            data-plan-notes="<?= htmlspecialchars($fu['next_follow_up_plan_notes'] ?? '') ?>">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </button>

                                        <button class="removeBtn_cust table__icon delete" data-id="<?= $fu['id'] ?>">
                                            <i class="fa-regular fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal remains same -->
<?php include 'follow_up_edit_modal.php'; ?>

<script>
    const projectSel = document.getElementById('projectSelect');
    const flatSel = document.getElementById('flatSelect');

    function loadFlats(projectId, selectedFlatId = null, targetSelect = flatSel) {
        targetSelect.innerHTML = '<option value="">Loading...</option>';

        fetch(`<?= $GLOBALS['base_url'] ?>/get-flats/${projectId}`)
            .then(res => res.json())
            .then(data => {
                flatData = data;
                flatSelect.innerHTML = '<option value="">Select Flat</option>';

                data.forEach(flat => {
                    const option = document.createElement('option');
                    const flatId = flat.flat_id ?? flat.id; // fallback: try flat.id
                    if (!flatId) return; // skip invalid entries

                    option.value = flatId;

                    if (flat.is_booked == 1) {
                        option.textContent = `${flat.flat_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft [Booked]`;
                        option.classList.add('booked-option');
                        option.disabled = true;
                    } else {
                        option.textContent = `${flat.flat_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft`;
                    }

                    flatSelect.appendChild(option);
                });

            });
    }

    projectSel.addEventListener('change', function() {
        if (this.value) loadFlats(this.value);
        else flatSel.innerHTML = '<option value="">Select Flat</option>';
    });

    // Lead Transferred toggle logic
    document.getElementById('create_lead_status').addEventListener('change', function() {
        const reassignDiv = document.getElementById('reassign_team_div');
        const reassignSelect = document.getElementById('reassign_team_id');
        if (this.value === 'Lead Transferred') {
            reassignDiv.style.display = 'block';
            reassignSelect.required = false;
        } else {
            reassignDiv.style.display = 'none';
            reassignSelect.required = false;
        }
    });

    // Edit Modal JS
    document.querySelectorAll('.table__icon.edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = document.getElementById('followUpModalEdit');
            if(!modal) return;
            
            // Basic fields
            modal.querySelector('input[name="id"]').value = this.dataset.id;
            modal.querySelector('select[name="lead_id"]').value = this.dataset.leadId;
            modal.querySelector('select[name="project_id"]').value = this.dataset.projectId;
            
            // Load flats for project and set selected
            if (this.dataset.projectId) {
                loadFlats(this.dataset.projectId, this.dataset.flatId, document.getElementById('editFlatSelect'));
            } else {
                document.getElementById('editFlatSelect').innerHTML = '<option value="">Select Flat</option>';
            }

            modal.querySelector('input[name="follow_up_date"]').value = this.dataset.date;
            modal.querySelector('input[name="follow_up_time"]').value = this.dataset.time;
            modal.querySelector('select[name="follow_up_type"]').value = this.dataset.type;
            modal.querySelector('textarea[name="discussion_note"]').value = this.dataset.note;
            modal.querySelector('input[name="next_follow_up_date"]').value = this.dataset.nextDate;
            modal.querySelector('select[name="next_action"]').value = this.dataset.nextAction;

            // New fields
            if (modal.querySelector('select[name="lead_status"]')) {
                modal.querySelector('select[name="lead_status"]').value = this.dataset.leadStatus;
            }
            if (modal.querySelector('select[name="follow_up_agent_id"]')) {
                modal.querySelector('select[name="follow_up_agent_id"]').value = this.dataset.agentId;
            }
            if (modal.querySelector('textarea[name="next_follow_up_plan_notes"]')) {
                modal.querySelector('textarea[name="next_follow_up_plan_notes"]').value = this.dataset.planNotes;
            }
        });
    });

</script>