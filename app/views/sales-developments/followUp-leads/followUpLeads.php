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
                    <!-- Lead -->
                    <!-- <div class="col-lg-6">
                        <label class="form-label">Select Lead*</label>
                        <select name="lead_id" class="form-select" required>
                            <option value="">Select Lead</option>
                            <php foreach ($leads as $lead): ?>
                                <option value="<= $lead['id'] ?>"><= $lead['full_name'] ?> - <= $lead['lead_quality'] ?></option>
                            <php endforeach; ?>
                        </select>
                    </div> -->

                    <!-- Project -->
                    <div class="col-lg-6">
                        <label class="form-label">Project</label>
                        <select name="project_id" id="projectSelect" class="form-select">
                            <option value="">Select Project</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?= $project['id'] ?>"><?= $project['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Flat -->
                    <!-- <div class="col-lg-6">
                        <label class="form-label">Flat</label>
                        <select name="flat_id" id="flatSelect" class="form-select">
                            <option value="">Select Flat</option>
                        </select>
                    </div> -->
                    <!-- Flat -->
                    <div class="col-lg-6">
                        <label class="form-label">Flat / Unit *</label>
                        <select id="flatSelect" name="flat_id" class="form-select " data-live-search="true">

                            <option value="">Select Flat</option>
                        </select>
                    </div>

                    <!-- Follow Up Date -->
                    <div class="col-lg-6">
                        <label class="form-label">Follow Up Date*</label>
                        <input type="date" name="follow_up_date" class="form-control" required>
                    </div>

                    <!-- Follow Up Time -->
                    <div class="col-lg-6">
                        <label class="form-label">Follow Up Time</label>
                        <input type="time" name="follow_up_time" class="form-control">
                    </div>

                    <!-- Follow Up Type -->
                    <div class="col-lg-6">
                        <label class="form-label">Follow Up Type*</label>
                        <select name="follow_up_type" class="form-select" required>
                            <option value="">Select Type</option>
                            <option value="call">Call</option>
                            <option value="visit">Visit</option>
                            <option value="meeting">Meeting</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="email">Email</option>
                        </select>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">Assign user For follow up*</label>
                        <select name="assigned_with" class="form-select">
                            <option value="">Select User</option>
                            <?php foreach ($assignedUsers as $user): ?>
                                <option value="<?= $user['id'] ?>">
                                    <?= htmlspecialchars($user['full_name']) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <!-- Discussion Note -->
                    <div class="col-lg-12">
                        <label class="form-label">Discussion Note</label>
                        <textarea name="discussion_note" class="form-control" rows="3"></textarea>
                    </div>

                    <!-- Next Follow Up Date -->
                    <div class="col-lg-6">
                        <label class="form-label">Next Follow Up Date</label>
                        <input type="date" name="next_follow_up_date" class="form-control">
                    </div>

                    <!-- Next Action -->
                    <div class="col-lg-6">
                        <label class="form-label">Next Action</label>
                        <select name="next_action" class="form-select">
                            <option value="">Select Action</option>
                            <option value="site visit">Site Visit</option>
                            <option value="booking">Booking</option>
                            <option value="call_back">Call Back</option>
                            <option value="take_time">Take Time</option>
                        </select>
                    </div>

                </div>
                <div class="mt-3">
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
                            <th class="d-none">Created By</th>
                            <th class="d-none">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
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
                                <td class="d-none"><?= $fu['created_by_name'] ?? '' ?></td>
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
                                            data-next-action="<?= $fu['next_action'] ?>">
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

    // Edit modal JS remains same
</script>