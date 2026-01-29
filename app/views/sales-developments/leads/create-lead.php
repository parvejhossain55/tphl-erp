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
                        <label class="form__input-title" for="leadName">Lead Name*</label>
                        <input class="form-control" name="full_name" id="leadName" type="text">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title" for="leadName">Father Name</label>
                        <input class="form-control" name="father_name" id="father_name" type="text">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title" for="leadName">Mother Name</label>
                        <input class="form-control" name="mother_name" id="mother_name" type="text">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="from__input-box">
                        <div class="form__input-title">
                            <label class="form-check-label">Date Of Birth <span>*</span></label>
                        </div>
                        <div class="form__input">
                            <input name="dob" class="form-control" id="humanFriendlyDates" type="text" readonly="readonly">
                        </div>
                    </div>
                </div>
                <!-- designation  -->
                <div class="col-md-6">
                    <div class="from__input-box">
                        <label>Profession</label>
                        <input type="text" class="form-control" name="designation">
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
                        <input class="form-control" name="company" id="companyName" type="text">
                    </div>
                </div>

                <!-- Phone -->
                <div class="col-md-6">
                    <div class="from__input-box">
                        <label class="form__input-title" for="phoneNumber">Mobile Number*</label>
                        <input class="form-control" name="phone" id="phoneNumber" type="text">
                    </div>
                </div>

                <!-- Alternate Phone -->
                <div class="col-md-6">
                    <div class="from__input-box">
                        <label for="altPhone">Alternate Number</label>
                        <input class="form-control" name="alt_phone_number" id="altPhone" type="text">
                    </div>
                </div>

                <!-- Email -->
                <div class="col-md-6">
                    <div class="from__input-box">
                        <label class="form__input-title" for="emailAddress">Email*</label>
                        <input class="form-control" name="email" id="emailAddress" type="email">
                    </div>
                </div>

                <!-- Source -->
                <div class="col-xl-6">
                    <label>Lead Source *</label>
                    <div class="d-flex gap-2">
                        <select name="source_type_id" class="form-control" required style="flex:1;">
                            <option value="">Select Source</option>
                            <?php foreach ($sources as $source): ?>
                                <option value="<?= $source['id']; ?>"><?= $source['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <a href="<?= $GLOBALS['base_url'] ?>/leadSource-lists"
                            class="btn btn-secondary"
                            style="height: 38px; display:flex; align-items:center; padding:0 12px;">
                            + source
                        </a>
                    </div>
                </div>

                <!-- Preferred Location -->
                <div class="col-md-6">
                    <div class="from__input-box">
                        <label>Preferred Location</label>
                        <input type="text" class="form-control" name="pref_location">
                    </div>
                </div>
                <!-- Tags -->
                <div class="col-md-6">
                    <div class="tagify__wrapper">
                        <label>Extra notes</label>
                        <input id="TagifyBasic" class="form-control" name="tags">
                    </div>
                </div>

                <!-- Project Type -->
                <div class="col-md-6">
                    <div class="from__input-box">
                        <label>Preferred Project *</label>
                        <select name="project_type" class="form-select">
                            <option value="ATI Society">ATI Society</option>
                            <option value="ASHU Society">ASHU Society</option>
                        </select>
                    </div>
                </div>

                <!-- Interest On -->
                <div class="col-md-6 d-none">
                    <div class="from__input-box">
                        <label>Interest On*</label>
                        <input type="text" class="form-control" name="interest_on">
                    </div>
                </div>

                <!-- Preferred Flat Size -->
                <div class="col-md-6 ">
                    <div class="from__input-box">
                        <label>Preferred Size*</label>
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
                        <label>Present Address</label>
                        <textarea class="form-control" name="address"></textarea>
                    </div>
                </div>
                <!-- Description -->
                <div class="col-md-6">
                    <div class="from__input-box">
                        <label>Permanent Address</label>
                        <textarea class="form-control" name="permanent_address"></textarea>
                    </div>
                </div>
                <!-- Preferred Flat Size -->
                <div class="col-md-6 ">
                    <div class="from__input-box">
                        <label>NID/Passpor Number*</label>
                        <input type="text" class="form-control" name="nid_or_passport">
                    </div>
                </div>

                <!-- Visibility -->

                <div class="col-lg-6">
                    <div class="from__input-box">
                        <label class="form__input-title" for="assignedUser">Assigned User*</label>
                        <select name="assigned_id" id="assignedUser" class="form-select">
                            <option value="">Select</option>
                            <?php foreach ($assignedUsers as $user) : ?>
                                <option value="<?= $user['id'] ?>"><?= $user['full_name'] ?></option>
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

<!-- new lead modal end -->