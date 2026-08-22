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
                            <label class="form-label">Select Lead</label>
                            <select name="lead_id" class="form-select">
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
                            <select name="project_id" id="editProjectSelect" class="form-select select2-no-scroll" data-placeholder="Select Project">
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
                        <div class="col-lg-6">
                            <label class="form-label">Flat</label>
                            <select name="flat_id" id="editFlatSelect" class="form-select select2-no-scroll" data-placeholder="Select Flat">
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
                            <select name="follow_up_type" id="edit_follow_up_type" class="form-select" required>
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
                            <label class="form-label">Lead Status</label>
                            <select name="lead_status" id="edit_lead_status" class="form-select">
                                <option value="">Select Status</option>
                                <?php
                                $statuses = [
                                    'No Response', 'Pending', 'Prospect', 'High Prospect', 
                                    'Will Visit', 'Visited', 'Sold', 'Lead Transferred', 
                                    'Project Transferred', 'Not Interested', 'Don\'t Call Again', 
                                    'Refund', 'Future', 'Cold', 'Hot', 'Warm'
                                ];
                                foreach($statuses as $st) {
                                    echo "<option value=\"$st\">$st</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <!-- Follow Up Agent (Optional) -->
                        <div class="col-lg-6">
                            <label class="form-label">Follow-up Agent (Optional)</label>
                            <select name="follow_up_agent_id" id="edit_follow_up_agent_id" class="form-select">
                                <option value="">Myself (Default)</option>
                                <?php foreach (($teamMembers ?? []) as $member): ?>
                                    <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Discussion Note -->
                        <div class="col-lg-12">
                            <label class="form-label">Discussion Note*</label>
                            <textarea name="discussion_note" class="form-control" rows="3" required></textarea>
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

                        <!-- Next Follow Up Plan Notes -->
                        <div class="col-lg-12">
                            <label class="form-label">Next Follow Up Plan Notes</label>
                            <textarea name="next_follow_up_plan_notes" class="form-control" rows="2"></textarea>
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
