<?php
$page = "create Lead"
?>
<!-- new lead modal start -->
<div class="row">
    <div class="card__wrapper">
        <form action="<?= $GLOBALS['base_url'] ?>/store-lead" method="POST">
            <div class="row gy-10">


                <!-- Lead Name -->
                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title" for="leadName">Lead Name <span class="text-danger">*</span></label>
                        <input class="form-control" name="full_name" id="leadName" type="text">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title" for="leadName">Father Name <span class="text-mute">optional</span></label>
                        <input class="form-control" name="father_name" id="father_name" type="text">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title" for="leadName">Mother Name <span class="text-mute">(optional)</span></label>
                        <input class="form-control" name="mother_name" id="mother_name" type="text">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="from__input-box">
                        <div class="form__input-title">
                            <label class="form-check-label">Date Of Birth <small class="text-dark">(optional)</small></label>
                        </div>
                        <div class="form__input">
                            <input name="dob" class="form-control" id="humanFriendlyDates" type="text" readonly="readonly">
                        </div>
                    </div>
                </div>
                <!-- designation  -->
                <div class="d-none col-md-6">
                    <div class="from__input-box">
                        <label>Profession</label>
                        <input type="text" class="form-control" name="designation">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title">Project Campaign Name <span class="text-mute">(optional)</span></label>
                        <input type="text" class="form-control" name="campaign_name">
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title">Platform </label><span class="text-mute">(optional)</span>
                        <input type="text" class="form-control" name="platform">
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title">Which Apartment Size Interested <span class="text-mute">(optional)</span> </label>
                        <input type="text" class="form-control" name="which_apartment_size_are_you_interested_in">
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title">Which Floor for Shop <span class="text-mute">(optional)</span></label>
                        <input type="text" class="form-control" name="which_floor_are_you_for_shop">
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title">When Are Planning to Buy <span class="text-mute">(optional)</span> </label>
                        <input type="date" class="form-control" name="when_are_you_planning_to_buy">
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title">Would You Like to Schedule a Site Visit? <span class="text-mute">(optional)</span></label>
                        <select class="form-select" name="would_you_like_to_schedule_a_site_visit">
                            <option value="">Select</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title">Best Time to Contact</label>
                        <input type="text" class="form-control" name="when_is_the_best_time_to_contact_you">
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title">Job Title</label>
                        <input type="text" class="form-control" name="job_title">
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title">City <span class="text-mute">(optional)</span></label>
                        <input type="text" class="form-control" name="city">
                    </div>
                </div>

                <!-- Lead Type -->
                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title" for="leadType">Lead Type*</label>
                        <select name="lead_type" id="leadType" class="form-select">
                            <option value="">Select</option>
                            <option value="Hot">Hot</option>
                            <option value="Warm">Warm</option>
                            <option value="Cold">Cold</option>
                        </select>
                    </div>
                </div>

                <!-- Company Name -->
                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title" for="companyName">Company Name (optional)</label>
                        <input class="form-control" name="company_name" id="companyName" type="text">
                    </div>
                </div>

                <!-- Phone -->
                <div class="col-md-6">
                    <div class="from__input-box">
                        <label class="form__input-title" for="phoneNumber">Mobile Number <span class="text-danger">*</span></label>
                        <input class="form-control" name="phone_number" id="phoneNumber" type="text">
                    </div>
                </div>

                <!-- Alternate Phone -->
                <div class="col-md-6">
                    <div class="from__input-box">
                        <label for="altPhone">Alternate Number <span class="text-mute">(optional)</span></label>
                        <input class="form-control" name="alt_phone_number" id="altPhone" type="text">
                    </div>
                </div>

                <!-- Email -->
                <div class="col-md-6">
                    <div class="from__input-box">
                        <label class="form__input-title" for="emailAddress">Email* <span class="text-danger">*</span></label>
                        <input class="form-control" name="email" id="emailAddress" type="email">
                    </div>
                </div>

                <!-- Source -->
                <div class="col-xl-6">
                    <label>Lead Source <span class="text-danger">*</span></label>
                    <div class="d-flex gap-2">
                        <select name="source_type_id" class="form-control" style="flex:1;">
                            <option value="">Select Source </option>
                            <?php foreach ($sources as $source): ?>
                                <option value="<?= $source['id']; ?>"><?= $source['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNewLeadSource"
                            style="height: 38px; display:flex; align-items:center; padding:0 12px;">
                            + Source
                        </button>


                    </div>
                </div>

                <!-- Preferred Location -->
                <div class="col-md-6">
                    <div class="from__input-box">
                        <label>Preferred Location <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2">
                            <select name="sourcepref_location_id_type_id" class="form-control" required style="flex:1;">
                                <option value="">Select Location</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?= $location['id']; ?>"><?= $location['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNewLocation"
                                style="height: 38px; display:flex; align-items:center; padding:0 12px;">
                                + Location
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Tags -->
                <div class="col-md-6">
                    <div class="tagify__wrapper">
                        <label>Extra notes <span class="text-mute">(optional)</span></label>
                        <input id="TagifyBasic" class="form-control" name="tags">
                    </div>
                </div>

                <!-- Project Type -->
                <div class="col-md-6 ">
                    <div class="from__input-box">
                        <label>Preferred Project Optional</label>
                        <select name="project_id" class="form-select">
                         <option value="">Select Project</option>
                         <?php foreach ($projects as $project): ?>
                             <option value="<?= $project['id']; ?>"><?= $project['name']; ?></option>
                            <?php endforeach; ?>
       
                        </select>
                    </div>
                </div>

                <!-- Interest On -->
                <div class="col-md-6 d-none">
                    <div class="from__input-box">
                        <label>Prject Campaign Name <span class="text-mute">(optional)</span></label>
                        <input type="text" class="form-control" name="campaign_name">
                    </div>
                </div>

                <!-- Preferred Flat Size -->
                <div class="col-md-6 ">
                    <div class="from__input-box">
                        <label>Preferred Size <span class="text-mute">(optional)</span></label>
                        <input type="text" class="form-control" name="pref_flat_size">
                    </div>
                </div>

                <!-- Communication Type -->
                <div class="col-md-6 d-none">
                    <div class="from__input-box d-none">
                        <label>Communication Type</label>
                        <select name="communication_type" class="form-select">
                            <option value="WA Messaging">WA Messaging</option>
                            <option value="WA Conversion">WA Conversion</option>
                        </select>
                    </div>
                </div>

                <!-- Lead Quality -->
                <div class="col-md-6">
                    <label>Lead Quality</label>
                    <select name="lead_quality" class="form-select">
                        <option value="Not Interested">Not Interested</option>
                        <option value="Prospect">Prospect</option>
                        <option value="Pending">Pending follow Up</option>
                    </select>
                </div>

                <!-- Address -->
                <div class="col-md-6">
                    <div class="from__input-box">
                        <label>Present Address <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="address"></textarea>
                    </div>
                </div>
                <!-- Description -->
                <div class="col-md-6">
                    <div class="from__input-box">
                        <label>Permanent Address <span class="text-mute">(optional)</span></label>
                        <textarea class="form-control" name="permanent_address"></textarea>
                    </div>
                </div>
                <!-- Preferred Flat Size -->
                <div class="col-md-6 ">
                    <div class="from__input-box">
                        <label>NID/Passpor Number <span class="text-mute">(optional)</span></label>
                        <input type="text" class="form-control" name="nid_or_passport">
                    </div>
                </div>

                <!-- Visibility -->

                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title" for="assignedUser">Assigned User <span class="text-danger">*</span></label>
                        <select name="assigned_id" id="assignedUser" class="form-select">
                            <option value="">Select</option>
                            <?php foreach ($assignedUsers as $user) : ?>
                                <option value="<?= $user['id'] ?>"><?= $user['full_name'] ?> - <?= $user['role_name'] ?> </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Status -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Status</label>
                    <div class="border rounded p-3 bg-white">
                        <div class="d-flex gap-3">
                            <div class="status-radio">
                                <input type="radio" name="status" value="active" id="active" checked>
                                <label for="active" class="badge bg-success fs-6 py-2 px-4">
                                    ✓ Active
                                </label>
                            </div>
                            <div class="status-radio ms-3">
                                <input type="radio" name="status" value="inactive" id="inactive">
                                <label for="inactive" class="badge bg-danger fs-6 py-2 px-4">
                                    ✕ Inactive
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="submit__btn text-center mt-4">
                <button class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>
</div>

<!-- new Location lead modal start -->
<div id="addNewLocation" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New location</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close"><i
                        class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/store-location-lead" method="POST">
                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="name">Location Name<span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="name" id="name" type="text" required>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="submit__btn text-center">
                        <button class="btn btn-primary">Submit</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
<!-- new lead modal end -->


<!-- new lead Source modal start -->
<div id="addNewLeadSource" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Lead Source</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close"><i
                        class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/store-source-lead" method="POST">
                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="name">Lead Source Name<span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="name" id="name" type="text" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form__input-title">
                                            <label>Status</label>
                                        </div>
                                        <div class="radio d-flex align-items-center gap-20">
                                            <div class="form-check">
                                                <input name="is_active" class="form-check-input" type="radio" value="1" id="statusActive" checked>
                                                <label class="form-check-label" for="statusActive">Active</label>
                                            </div>
                                            <div class="form-check">
                                                <input name="is_active" class="form-check-input" type="radio" value="0" id="statusInactive">
                                                <label class="form-check-label" for="statusInactive">Inactive</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="submit__btn text-center">
                        <button class="btn btn-primary">Submit</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
<!-- new lead modal end -->