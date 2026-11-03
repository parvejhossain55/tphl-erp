<?php
$page = "Edit Lead";

$selectedSourceId   = $_SESSION['old']['source_type_id']   ?? $leadData['source_type_id']   ?? null;
$selectedProjectId  = $_SESSION['old']['project_id']      ?? $leadData['project_id']      ?? null;
$selectedCategoryId = $_SESSION['old']['project_category_id'] ?? $leadData['project_category_id'] ?? null;
$selectedLocId      = $_SESSION['old']['pref_location_id'] ?? $leadData['pref_location_id'] ?? null;
$selectedAssignedId = $_SESSION['old']['assigned_id']      ?? $leadData['assigned_id']      ?? null;
$selectedTeamId     = $_SESSION['old']['team_id']          ?? $leadData['team_id']          ?? null;
?>

<div class="row">
    <div class="col-12">
        <div class="card__wrapper p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                <h4 class="mb-0 text-primary">
                    <i class="fa-solid fa-user-pen me-2"></i>Edit Lead Information: <?= htmlspecialchars($leadData['full_name'] ?? $leadData['lead_name'] ?? 'Lead #' . $leadData['id'], ENT_QUOTES, 'UTF-8') ?>
                </h4>
                <a href="<?= $GLOBALS['base_url'] ?>/view-lead/<?= $leadData['id'] ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-eye me-1"></i> View Lead
                </a>
            </div>

            <form action="<?= $GLOBALS['base_url'] ?>/update-lead/<?= $leadData['id'] ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect ?? '') ?>">
                <div class="row ">

                    <!-- ========================================================================= -->
                    <!-- SECTION 1: PERSONAL INFORMATION -->
                    <!-- ========================================================================= -->
                    <div class="col-12 mt-2">
                        <div class="p-2 bg-light rounded text-dark fw-bold border-start border-4 border-primary fs-6">
                            <i class="fa-solid fa-user me-2 text-primary"></i>1. Personal Information (ব্যক্তিগত তথ্য)
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Full Name (English)</label>
                        <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($leadData['full_name'] ?? $leadData['lead_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">নাম (বাংলা)</label>
                        <input type="text" class="form-control" name="lead_name_bn" value="<?= htmlspecialchars($leadData['lead_name_bn'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Father Name (English)</label>
                        <input type="text" class="form-control" name="father_name" value="<?= htmlspecialchars($leadData['father_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">পিতার নাম (বাংলা)</label>
                        <input type="text" class="form-control" name="father_name_bn" value="<?= htmlspecialchars($leadData['father_name_bn'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Mother Name (English)</label>
                        <input type="text" class="form-control" name="mother_name" value="<?= htmlspecialchars($leadData['mother_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">মাতার নাম (বাংলা)</label>
                        <input type="text" class="form-control" name="mother_name_bn" value="<?= htmlspecialchars($leadData['mother_name_bn'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Date of Birth</label>
                        <input type="date" class="form-control" name="dob" value="<?= htmlspecialchars($leadData['dob'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Nationality</label>
                        <input type="text" class="form-control" name="nationality" value="<?= htmlspecialchars($leadData['nationality'] ?? 'Bangladeshi', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Religion</label>
                        <select name="religion" class="form-select">
                            <option value="">-- Select Religion --</option>
                            <?php foreach (['Islam', 'Hindu', 'Christian', 'Buddhist', 'Other'] as $rel): ?>
                                <option value="<?= $rel ?>" <?= ($leadData['religion'] ?? '') === $rel ? 'selected' : '' ?>><?= $rel ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Residency Type</label>
                        <select name="residency_type" class="form-select">
                            <option value="local" <?= ($leadData['residency_type'] ?? '') === 'local' ? 'selected' : '' ?>>Local Resident</option>
                            <option value="expatriate" <?= ($leadData['residency_type'] ?? '') === 'expatriate' ? 'selected' : '' ?>>NRB (Non-Resident)</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Identity Document Type</label>
                        <select name="identity_type" class="form-select">
                            <option value="NID" <?= ($leadData['identity_type'] ?? '') === 'NID' ? 'selected' : '' ?>>NID</option>
                            <option value="Passport" <?= ($leadData['identity_type'] ?? '') === 'Passport' ? 'selected' : '' ?>>Passport</option>
                            <option value="Birth Certificate" <?= ($leadData['identity_type'] ?? '') === 'Birth Certificate' ? 'selected' : '' ?>>Birth Certificate</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">NID / Passport / ID Number</label>
                        <input type="text" class="form-control" name="nid_or_passport" value="<?= htmlspecialchars($leadData['nid_or_passport'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Client Photo</label>
                        <input type="file" class="form-control" name="lead_image" accept="image/*">
                        <?php if (!empty($leadData['lead_image'])): ?>
                            <div class="mt-2">
                                <img src="<?= getImage('leads', $leadData['lead_image']) ?>" class="rounded img-thumbnail" style="height: 60px;">
                                <small class="text-muted ms-2">Current Photo</small>
                            </div>
                        <?php endif; ?>
                    </div>


                    <!-- ========================================================================= -->
                    <!-- SECTION 2: CONTACT & ADDRESS INFORMATION -->
                    <!-- ========================================================================= -->
                    <div class="col-12 mt-4">
                        <div class="p-2 bg-light rounded text-dark fw-bold border-start border-4 border-success fs-6">
                            <i class="fa-solid fa-address-book me-2 text-success"></i>2. Contact & Address Information (যোগাযোগ ও ঠিকানা)
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Primary Phone (WhatsApp) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="phone" required value="<?= htmlspecialchars($leadData['phone'] ?? $leadData['phone_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Alternate Phone Number</label>
                        <input type="text" class="form-control" name="alt_phone_number" value="<?= htmlspecialchars($leadData['alt_phone_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($leadData['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Alternate Email Address</label>
                        <input type="email" class="form-control" name="alt_email" value="<?= htmlspecialchars($leadData['alt_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <!-- 2nd Owner Information Toggle -->
                    <div class="col-12 mt-3 mb-2">
                        <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#secondOwnerSection" aria-expanded="<?= !empty($leadData['2nd_lead_owner_name']) ? 'true' : 'false' ?>" aria-controls="secondOwnerSection">
                            <i class="fa-solid fa-plus me-1"></i> Add / View 2nd Owner Information
                        </button>
                    </div>

                    <div class="collapse col-12 <?= !empty($leadData['2nd_lead_owner_name']) ? 'show' : '' ?>" id="secondOwnerSection">
                        <div class="row bg-light p-3 border rounded">
                            <div class="col-12 mb-3">
                                <h6 class="fw-bold"><i class="fa-solid fa-user-plus me-2"></i>2nd Owner Details</h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">2nd Owner Name</label>
                                <input type="text" class="form-control" name="2nd_lead_owner_name" value="<?= htmlspecialchars($leadData['2nd_lead_owner_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">2nd Owner Phone</label>
                                <input type="text" class="form-control" name="2nd_owner_phone" value="<?= htmlspecialchars($leadData['2nd_owner_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">2nd Owner Email</label>
                                <input type="email" class="form-control" name="2nd_owner_email" value="<?= htmlspecialchars($leadData['2nd_owner_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">2nd Owner Address</label>
                                <input type="text" class="form-control" name="2nd_owner_address" value="<?= htmlspecialchars($leadData['2nd_owner_address'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Other Details</label>
                                <textarea class="form-control" name="2nd_owner_other_details" rows="2"><?= htmlspecialchars($leadData['2nd_owner_other_details'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Present Address (English)</label>
                        <textarea class="form-control" name="address" rows="2"><?= htmlspecialchars($leadData['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">বর্তমান ঠিকানা (বাংলা)</label>
                        <textarea class="form-control" name="present_add_bn" rows="2"><?= htmlspecialchars($leadData['present_add_bn'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Permanent Address (English)</label>
                        <textarea class="form-control" name="permanent_address" rows="2"><?= htmlspecialchars($leadData['permanent_address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">স্থায়ী ঠিকানা (বাংলা)</label>
                        <textarea class="form-control" name="permanent_add_bn" rows="2"><?= htmlspecialchars($leadData['permanent_add_bn'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">City</label>
                        <input type="text" class="form-control" name="city" value="<?= htmlspecialchars($leadData['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Country</label>
                        <input type="text" class="form-control" name="country" value="<?= htmlspecialchars($leadData['country'] ?? 'Bangladesh', ENT_QUOTES, 'UTF-8') ?>">
                    </div>


                    <!-- ========================================================================= -->
                    <!-- SECTION 3: PROFESSIONAL INFORMATION -->
                    <!-- ========================================================================= -->
                    <div class="col-12 mt-4">
                        <div class="p-2 bg-light rounded text-dark fw-bold border-start border-4 border-info fs-6">
                            <i class="fa-solid fa-briefcase me-2 text-info"></i>3. Professional Information (পেশাগত তথ্য)
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Designation / Profession</label>
                        <input type="text" class="form-control" name="designation" value="<?= htmlspecialchars($leadData['designation'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">পেশা (বাংলা)</label>
                        <input type="text" class="form-control" name="profession_bn" value="<?= htmlspecialchars($leadData['profession_bn'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Job Title</label>
                        <input type="text" class="form-control" name="job_title" value="<?= htmlspecialchars($leadData['job_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Company Name</label>
                        <input type="text" class="form-control" name="company_name" value="<?= htmlspecialchars($leadData['company_name'] ?? $leadData['company'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>


                    <!-- ========================================================================= -->
                    <!-- SECTION 4: CAMPAIGN, SOURCE & SYSTEM STATUS -->
                    <!-- ========================================================================= -->
                    <div class="col-12 mt-4">
                        <div class="p-2 bg-light rounded text-dark fw-bold border-start border-4 border-warning fs-6">
                            <i class="fa-solid fa-bullhorn me-2 text-warning"></i>4. Campaign, Source & System Meta (ক্যাম্পেইন, সোর্স ও সিস্টেম স্ট্যাটাস)
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Campaign Name</label>
                        <input type="text" class="form-control" name="campaign_name" value="<?= htmlspecialchars($leadData['campaign_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Platform</label>
                        <input type="text" class="form-control" name="platform" placeholder="e.g. Facebook, Google, Website, WhatsApp" value="<?= htmlspecialchars($leadData['platform'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Uploaded From</label>
                        <input type="text" class="form-control" name="uploaded_from" value="<?= htmlspecialchars($leadData['uploaded_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Lead Source</label>
                        <select name="source_type_id" class="form-select">
                            <option value="">-- Select Source --</option>
                            <?php if (!empty($sources)): ?>
                                <?php foreach ($sources as $source): ?>
                                    <option value="<?= $source['id'] ?>" <?= (string)$selectedSourceId === (string)$source['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($source['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Assigned Agent</label>
                        <select name="assigned_id" class="form-select">
                            <option value="">-- Select Agent --</option>
                            <?php if (!empty($assignedUsers)): ?>
                                <?php foreach ($assignedUsers as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= (string)$selectedAssignedId === (string)$user['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['full_name']) ?> (<?= htmlspecialchars($user['role_name'] ?? 'Agent') ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Assigned Team</label>
                        <select name="team_id" class="form-select">
                            <option value="">-- Select Team --</option>
                            <?php if (!empty($teams)): ?>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= $team['id'] ?>" <?= (string)$selectedTeamId === (string)$team['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($team['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Lead Type</label>
                        <select name="lead_type" class="form-select">
                            <option value="Qualification in Progress" <?= ($leadData['lead_type'] ?? '') === 'Qualification in Progress' ? 'selected' : '' ?>>Qualification in Progress</option>
                            <option value="Potential" <?= in_array($leadData['lead_type'] ?? '', ['Potential', 'Prospect', 'Warm']) ? 'selected' : '' ?>>Potential</option>
                            <option value="High Potential" <?= in_array($leadData['lead_type'] ?? '', ['High Potential', 'High Prospect', 'Hot']) ? 'selected' : '' ?>>High Potential</option>
                            <option value="Visit Scheduled" <?= in_array($leadData['lead_type'] ?? '', ['Visit Scheduled', 'Will Visit']) ? 'selected' : '' ?>>Visit Scheduled</option>
                            <option value="Visit Completed" <?= in_array($leadData['lead_type'] ?? '', ['Visit Completed', 'Visited']) ? 'selected' : '' ?>>Visit Completed</option>
                            <option value="Not Interested" <?= in_array($leadData['lead_type'] ?? '', ['Not Interested', 'Cold']) ? 'selected' : '' ?>>Not Interested</option>
                            <option value="No Response" <?= ($leadData['lead_type'] ?? '') === 'No Response' ? 'selected' : '' ?>>No Response</option>
                            <option value="Pending" <?= ($leadData['lead_type'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Invalid Lead" <?= in_array($leadData['lead_type'] ?? '', ['Invalid Lead', 'Irrelevant']) ? 'selected' : '' ?>>Invalid Lead</option>
                            <option value="Sold" <?= ($leadData['lead_type'] ?? '') === 'Sold' ? 'selected' : '' ?>>Sold</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Lead Quality</label>
                        <select name="lead_quality" class="form-select">
                            <option value="Pending" <?= ($leadData['lead_quality'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Potential" <?= in_array($leadData['lead_quality'] ?? '', ['Potential', 'Prospect']) ? 'selected' : '' ?>>Potential</option>
                            <option value="Not Interested" <?= ($leadData['lead_quality'] ?? '') === 'Not Interested' ? 'selected' : '' ?>>Not Interested</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Lead Action Status</label>
                        <select name="last_lead_action" class="form-select">
                            <option value="new" <?= ($leadData['last_lead_action'] ?? '') === 'new' ? 'selected' : '' ?>>New</option>
                            <option value="follow up" <?= ($leadData['last_lead_action'] ?? '') === 'follow up' ? 'selected' : '' ?>>Follow Up</option>
                            <option value="contacted" <?= ($leadData['last_lead_action'] ?? '') === 'contacted' ? 'selected' : '' ?>>Contacted</option>
                            <option value="visited" <?= ($leadData['last_lead_action'] ?? '') === 'visited' ? 'selected' : '' ?>>Visited</option>
                            <option value="booked" <?= ($leadData['last_lead_action'] ?? '') === 'booked' ? 'selected' : '' ?>>Booked</option>
                            <option value="cancel" <?= ($leadData['last_lead_action'] ?? '') === 'cancel' ? 'selected' : '' ?>>Cancel</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Call Status</label>
                        <select name="call_status" class="form-select">
                            <option value="not_called" <?= ($leadData['call_status'] ?? '') === 'not_called' ? 'selected' : '' ?>>Not Called</option>
                            <option value="called" <?= ($leadData['call_status'] ?? '') === 'called' ? 'selected' : '' ?>>Called</option>
                            <option value="busy" <?= ($leadData['call_status'] ?? '') === 'busy' ? 'selected' : '' ?>>Busy</option>
                            <option value="no_answer" <?= ($leadData['call_status'] ?? '') === 'no_answer' ? 'selected' : '' ?>>No Answer</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Visibility</label>
                        <select name="visibility" class="form-select">
                            <option value="public" <?= ($leadData['visibility'] ?? '') === 'public' ? 'selected' : '' ?>>Public</option>
                            <option value="private" <?= ($leadData['visibility'] ?? '') === 'private' ? 'selected' : '' ?>>Private</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Record Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= ($leadData['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($leadData['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Budget / Deal Value Amount</label>
                        <input type="number" step="0.01" class="form-control" name="value_amount" value="<?= htmlspecialchars($leadData['value_amount'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Currency</label>
                        <input type="text" class="form-control" name="currency" value="<?= htmlspecialchars($leadData['currency'] ?? 'BDT', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Call Note</label>
                        <input type="text" class="form-control" name="call_note" value="<?= htmlspecialchars($leadData['call_note'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>


                    <!-- ========================================================================= -->
                    <!-- SECTION 5: CUSTOMER REQUIREMENTS & PREFERENCES -->
                    <!-- ========================================================================= -->
                    <div class="col-12 mt-4">
                        <div class="p-2 bg-light rounded text-dark fw-bold border-start border-4 border-danger fs-6">
                            <i class="fa-solid fa-city me-2 text-danger"></i>5. Customer Requirements & Preferences (গ্রাহকের চাহিদা ও পছন্দ)
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Interested Project</label>
                        <select name="project_id" class="form-select">
                            <option value="">-- Select Project --</option>
                            <?php if (!empty($projects)): ?>
                                <?php foreach ($projects as $proj): ?>
                                    <option value="<?= $proj['id'] ?>" <?= (string)$selectedProjectId === (string)$proj['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($proj['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Project Category</label>
                        <select name="project_category_id" class="form-select">
                            <option value="">-- Select Category --</option>
                            <?php if (!empty($projectCategories)): ?>
                                <?php foreach ($projectCategories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= (string)$selectedCategoryId === (string)$cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Preferred Location</label>
                        <select name="pref_location_id" class="form-select">
                            <option value="">-- Select Location --</option>
                            <?php if (!empty($locations)): ?>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= $loc['id'] ?>" <?= (string)$selectedLocId === (string)$loc['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($loc['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Interested Apartment / Flat Size</label>
                        <input type="text" class="form-control" name="which_apartment_size_are_you_interested_in" placeholder="e.g. 1400 SFT, 3 BHK" value="<?= htmlspecialchars($leadData['which_apartment_size_are_you_interested_in'] ?? $leadData['pref_flat_size'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Floor Choice (Shop / Unit)</label>
                        <input type="text" class="form-control" name="which_floor_are_you_for_shop" placeholder="e.g. 1st Floor, Ground Floor" value="<?= htmlspecialchars($leadData['which_floor_are_you_for_shop'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Planning to Buy Timeframe</label>
                        <input type="text" class="form-control" name="when_are_you_planning_to_buy" placeholder="e.g. Within 1 Month, 3 Months" value="<?= htmlspecialchars($leadData['when_are_you_planning_to_buy'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Site Visit Preference</label>
                        <input type="text" class="form-control" name="would_you_like_to_schedule_a_site_visit" placeholder="e.g. Yes, Next Friday" value="<?= htmlspecialchars($leadData['would_you_like_to_schedule_a_site_visit'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Best Time to Contact</label>
                        <input type="text" class="form-control" name="when_is_the_best_time_to_contact_you" placeholder="e.g. 6 PM - 8 PM" value="<?= htmlspecialchars($leadData['when_is_the_best_time_to_contact_you'] ?? $leadData['preferred_contact_time'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Communication Type</label>
                        <input type="text" class="form-control" name="communication_type" placeholder="e.g. WhatsApp, Call, In Person" value="<?= htmlspecialchars($leadData['communication_type'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tags</label>
                        <input type="text" class="form-control" name="tags" placeholder="e.g. Urgent, High Budget, VIP" value="<?= htmlspecialchars($leadData['tags'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>


                    <!-- ========================================================================= -->
                    <!-- SECTION 6: NOMINEE INFORMATION -->
                    <!-- ========================================================================= -->
                    <div class="col-12 mt-4">
                        <div class="p-2 bg-light rounded text-dark fw-bold border-start border-4 border-secondary fs-6">
                            <i class="fa-solid fa-users me-2 text-secondary"></i>6. Nominee Information (নমিনি সংক্রান্ত তথ্য)
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nominee Name</label>
                        <input type="text" class="form-control" name="nominee_name" value="<?= htmlspecialchars($leadData['nominee_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Relation with Applicant</label>
                        <input type="text" class="form-control" name="relation_with_lead" value="<?= htmlspecialchars($leadData['relation_with_lead'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nominee Father's Name</label>
                        <input type="text" class="form-control" name="nomi_father_name" value="<?= htmlspecialchars($leadData['nomi_father_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nominee Mother's Name</label>
                        <input type="text" class="form-control" name="nomi_mother_name" value="<?= htmlspecialchars($leadData['nomi_mother_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Nominee Profession</label>
                        <input type="text" class="form-control" name="nomi_profession" value="<?= htmlspecialchars($leadData['nomi_profession'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Nominee Date of Birth</label>
                        <input type="date" class="form-control" name="nomi_dob" value="<?= htmlspecialchars($leadData['nomi_dob'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Nominee NID / Passport</label>
                        <input type="text" class="form-control" name="nomi_nid_passport" value="<?= htmlspecialchars($leadData['nomi_nid_passport'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nominee Phone</label>
                        <input type="text" class="form-control" name="nomi_phone" value="<?= htmlspecialchars($leadData['nomi_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nominee Email</label>
                        <input type="email" class="form-control" name="nomi_email" value="<?= htmlspecialchars($leadData['nomi_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nominee Present Address</label>
                        <textarea class="form-control" name="nomi_present_add" rows="2"><?= htmlspecialchars($leadData['nomi_present_add'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nominee Permanent Address</label>
                        <textarea class="form-control" name="nomi_permanent_address" rows="2"><?= htmlspecialchars($leadData['nomi_permanent_address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nominee Photo</label>
                        <input type="file" class="form-control" name="nomi_image" accept="image/*">
                        <?php if (!empty($leadData['nomi_image'])): ?>
                            <div class="mt-2">
                                <img src="<?= getImage('leads', $leadData['nomi_image']) ?>" class="rounded img-thumbnail" style="height: 60px;">
                                <small class="text-muted ms-2">Current Photo</small>
                            </div>
                        <?php endif; ?>
                    </div>


                    <!-- ========================================================================= -->
                    <!-- SECTION 7: 2ND NOMINEE INFORMATION -->
                    <!-- ========================================================================= -->
                    <div class="col-12 mt-4">
                        <div class="p-2 bg-light rounded text-dark fw-bold border-start border-4 border-primary fs-6">
                            <i class="fa-solid fa-user-plus me-2 text-primary"></i>7. 2nd Nominee Information (২য় নমিনি সংক্রান্ত তথ্য)
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">2nd Nominee Name</label>
                        <input type="text" class="form-control" name="2nd_nominee_name" value="<?= htmlspecialchars($leadData['2nd_nominee_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Relation with Applicant</label>
                        <input type="text" class="form-control" name="2nd_relation_with_lead" value="<?= htmlspecialchars($leadData['2nd_relation_with_lead'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">2nd Nominee Father's Name</label>
                        <input type="text" class="form-control" name="2nd_nomi_father_name" value="<?= htmlspecialchars($leadData['2nd_nomi_father_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">2nd Nominee Mother's Name</label>
                        <input type="text" class="form-control" name="2nd_nomi_mother_name" value="<?= htmlspecialchars($leadData['2nd_nomi_mother_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">2nd Nominee Profession</label>
                        <input type="text" class="form-control" name="2nd_nomi_profession" value="<?= htmlspecialchars($leadData['2nd_nomi_profession'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">2nd Nominee Date of Birth</label>
                        <input type="date" class="form-control" name="2nd_nomi_dob" value="<?= htmlspecialchars($leadData['2nd_nomi_dob'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">2nd Nominee NID / Passport</label>
                        <input type="text" class="form-control" name="2nd_nomi_nid_passport" value="<?= htmlspecialchars($leadData['2nd_nomi_nid_passport'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">2nd Nominee Phone</label>
                        <input type="text" class="form-control" name="2nd_nomi_phone" value="<?= htmlspecialchars($leadData['2nd_nomi_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">2nd Nominee Email</label>
                        <input type="email" class="form-control" name="2nd_nomi_email" value="<?= htmlspecialchars($leadData['2nd_nomi_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">2nd Nominee Present Address</label>
                        <textarea class="form-control" name="2nd_nomi_present_add" rows="2"><?= htmlspecialchars($leadData['2nd_nomi_present_add'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">2nd Nominee Permanent Address</label>
                        <textarea class="form-control" name="2nd_nomi_permanent_address" rows="2"><?= htmlspecialchars($leadData['2nd_nomi_permanent_address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">2nd Nominee Photo</label>
                        <input type="file" class="form-control" name="2nd_nomi_image" accept="image/*">
                        <?php if (!empty($leadData['2nd_nomi_image'])): ?>
                            <div class="mt-2">
                                <img src="<?= getImage('leads', $leadData['2nd_nomi_image']) ?>" class="rounded img-thumbnail" style="height: 60px;">
                                <small class="text-muted ms-2">Current Photo</small>
                            </div>
                        <?php endif; ?>
                    </div>


                    <!-- ========================================================================= -->
                    <!-- SECTION 8: DESCRIPTION & NOTES -->
                    <!-- ========================================================================= -->
                    <div class="col-12 mt-4">
                        <div class="p-2 bg-light rounded text-dark fw-bold border-start border-4 border-dark fs-6">
                            <i class="fa-solid fa-file-lines me-2 text-dark"></i>8. Description & Additional Notes (বিবরণী)
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Lead Description / Notes</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Enter detailed notes or comments..."><?= htmlspecialchars($leadData['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                </div>

                <div class="text-center mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fa-solid fa-save me-2"></i> Update Lead Information
                    </button>
                    <a href="<?= htmlspecialchars($redirect ?: $GLOBALS['base_url'] . '/view-lead/' . $leadData['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary btn-lg px-4 ms-2">
                        <i class="fa-solid fa-times me-2"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
