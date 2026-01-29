<!-- Follow Up Edit Modal Start -->
<div class="modal fade" id="followUpModalEdit" tabindex="-1" aria-labelledby="followUpModalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="followUpModalEditLabel">Create Follow Up</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/update-follow-up" method="POST">
                <input type="hidden" name="id" id="edit_follow_up">
                <div class="modal-body">
                    <div class="row gy-3">

                        <!-- Select Lead -->
                        <div class="col-lg-6">
                            <label class="form-label">Select Lead*</label>
                            <select name="lead_id" class="form-select" required>
                                <option value="">Select Lead</option>
                                <?php foreach ($leads as $lead): ?>
                                    <option value="<?= $lead['id'] ?>"><?= $lead['full_name'] ?> - <?= $lead['lead_quality'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Project -->
                        <div class="col-lg-6">
                            <label class="form-label">Project</label>
                            <!-- Project -->
                            <select name="project_id" id="editProjectSelect" class="form-select">
                                <option value="">Select Project</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?= $project['id'] ?>"><?= $project['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Flat -->
                        <div class="col-lg-6">
                            <label class="form-label">Flat</label>
                            <select name="flat_id" id="editFlatSelect" class="form-select">
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
                </div>

                <div class="modal-footer">
                    <input type="hidden" name="created_by" value="<?= $_SESSION['user_id'] ?? 1 ?>">
                    <button type="submit" class="btn btn-primary">Save Follow Up</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>



        </div>
    </div>
</div>
<!-- Follow Up Edit Modal End -->
