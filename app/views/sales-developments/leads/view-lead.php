<?php
$page = "View Lead";
?>

<style>
    .lead-view-container {
        min-height: 100vh;
    }

    .lead-header-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .lead-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #fff;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    }

    .lead-name-section h2 {
        color: #2c3e50;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .badge-container {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }

    .custom-badge {
        padding: 0.5rem 1rem;
        border-radius: 25px;
        font-weight: 600;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .badge-followup {
        background: linear-gradient(135deg, #198754 0%, #072618 100%);
        color: white;
    }

    .badge-booking {
        background: linear-gradient(135deg, #1abc9c 0%, #1d7249 100%);
        color: white;
    }

    .info-card {
        background: #fdfdfd;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 1.5rem;
        overflow: hidden;
        border: 1px solid #eef2f5;
        height: calc(100% - 1.5rem);
    }

    .info-card-header {
        background: linear-gradient(135deg, #198754 0%, #072618 100%);
        color: white;
        padding: 1rem 1.5rem;
        font-weight: 600;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .info-card-header.header-contact {
        background: linear-gradient(135deg, #0d6efd 0%, #0a369d 100%);
    }

    .info-card-header.header-pro {
        background: linear-gradient(135deg, #0dcaf0 0%, #055160 100%);
        color: #000;
    }

    .info-card-header.header-meta {
        background: linear-gradient(135deg, #ffc107 0%, #664d03 100%);
        color: #000;
    }

    .info-card-header.header-req {
        background: linear-gradient(135deg, #dc3545 0%, #842029 100%);
    }

    .info-card-header.header-nomi {
        background: linear-gradient(135deg, #6c757d 0%, #343a40 100%);
    }

    .info-card-body {
        padding: 1.25rem 1.5rem;
    }

    .info-row {
        display: flex;
        padding: 0.65rem 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        flex: 0 0 45%;
        color: #6c757d;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .info-value {
        flex: 1;
        color: #2c3e50;
        font-weight: 500;
    }

    .btn-modern {
        padding: 0.6rem 1.5rem;
        border-radius: 25px;
        font-weight: 600;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
    }

    .btn-primary-modern {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        color: white;
    }

    .btn-secondary-modern {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        color: white;
    }

    .status-badge {
        padding: 0.35rem 0.85rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.82rem;
    }

    .status-active {
        background: #d1e7dd;
        color: #0f5132;
    }

    .status-inactive {
        background: #f8d7da;
        color: #842029;
    }
</style>

<div class="lead-view-container">
    <div class="container-fluid">

        <!-- Enhanced Header Card -->
        <div class="lead-header-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-4">

                <!-- Left Section: Avatar + Name + Phone + Badges -->
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3 flex-grow-1">
                    <div class="d-flex align-items-center gap-3">
                        <?php if (!empty($leadData['lead_image'])): ?>
                            <img src="<?= getImage('leads', $leadData['lead_image']) ?>" class="lead-avatar" alt="Lead Photo">
                        <?php else: ?>
                            <div class="lead-avatar" style="background: linear-gradient(135deg, #198754 0%, #072618 100%); display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-user fa-2x text-white"></i>
                            </div>
                        <?php endif; ?>

                        <div class="lead-name-section me-2">
                            <h2 class="mb-0 fs-3">
                                <?= htmlspecialchars($leadData['full_name'] ?? $leadData['lead_name'] ?? 'Lead #' . $leadData['id']) ?>
                                <?php if (!empty($leadData['lead_name_bn'])): ?>
                                    <small class="text-muted fs-6">(<?= htmlspecialchars($leadData['lead_name_bn']) ?>)</small>
                                <?php endif; ?>
                            </h2>
                            <p class="text-muted mb-0 mt-1">
                                <i class="fa-solid fa-phone me-1 text-primary"></i> <?= htmlspecialchars($leadData['phone'] ?? $leadData['phone_number'] ?? 'N/A') ?>
                                <?php if (!empty($leadData['email'])): ?>
                                    <span class="ms-3"><i class="fa-solid fa-envelope me-1 text-danger"></i> <?= htmlspecialchars($leadData['email']) ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div class="badge-container">
                        <span class="custom-badge badge-followup">
                            <i class="fa-solid fa-calendar-check me-1"></i> Follow Ups:
                            <span class="badge bg-white text-dark rounded-pill ms-1"><?= $followUpCounts[$leadData['id']] ?? 0 ?></span>
                        </span>
                        <a href="<?= $GLOBALS['base_url'] ?>/booking-leads/<?= $leadData['id'] ?>" class="custom-badge badge-booking text-decoration-none">
                            <i class="fa-solid fa-building me-1"></i> Bookings:
                            <span class="badge bg-white text-dark rounded-pill ms-1"><?= $bookingCount[$leadData['id']] ?? 0 ?></span>
                        </a>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="<?= $GLOBALS['base_url'] ?>/edit-lead/<?= $leadData['id'] ?>" class="btn-modern btn-primary-modern">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Lead All Fields
                    </a>
                    <a href="javascript:history.back()" class="btn-modern btn-secondary-modern">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- 1. Personal Information Card -->
            <div class="col-lg-6">
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fa-solid fa-user me-2"></i> 1. Personal Information (ব্যক্তিগত তথ্য)
                    </div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <div class="info-label">Full Name (English)</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['full_name'] ?? $leadData['lead_name'] ?? 'N/A') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">নাম (বাংলা)</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['lead_name_bn'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Father's Name</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['father_name'] ?: '-') ?> (<?= htmlspecialchars($leadData['father_name_bn'] ?: '-') ?>)</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Mother's Name</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['mother_name'] ?: '-') ?> (<?= htmlspecialchars($leadData['mother_name_bn'] ?: '-') ?>)</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Date of Birth</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['dob'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Nationality & Religion</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['nationality'] ?? 'Bangladeshi') ?> / <?= htmlspecialchars($leadData['religion'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Residency Type</div>
                            <div class="info-value"><span class="badge bg-info text-dark"><?= strtoupper(htmlspecialchars($leadData['residency_type'] ?? 'local')) ?></span></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><?= htmlspecialchars($leadData['identity_type'] ?? 'NID/Passport') ?></div>
                            <div class="info-value"><?= htmlspecialchars($leadData['nid_or_passport'] ?: '-') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Contact & Address Information Card -->
            <div class="col-lg-6">
                <div class="info-card">
                    <div class="info-card-header header-contact">
                        <i class="fa-solid fa-address-card me-2"></i> 2. Contact & Address Information (যোগাযোগ ও ঠিকানা)
                    </div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <div class="info-label">Primary Mobile (WhatsApp)</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['phone'] ?? $leadData['phone_number'] ?? 'N/A') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Alternate Phone</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['alt_phone_number'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Email Address</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['email'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Alternate Email Address</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['alt_email'] ?: '-') ?></div>
                        </div>
                        

                        <div class="info-row">
                            <div class="info-label">Present Address (ENG)</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['address'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">বর্তমান ঠিকানা (BAN)</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['present_add_bn'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Permanent Address (ENG)</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['permanent_address'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">স্থায়ী ঠিকানা (BAN)</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['permanent_add_bn'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">City & Country</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['city'] ?: '-') ?>, <?= htmlspecialchars($leadData['country'] ?: '-') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2nd Owner Information Card -->
            <?php if (!empty($leadData['2nd_lead_owner_name'])): ?>
            <div class="col-lg-6 mb-3">
                <div class="info-card">
                    <div class="info-card-header bg-secondary text-white">
                        <i class="fa-solid fa-user-plus me-2"></i> 2nd Owner Details
                    </div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <div class="info-label">Name</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['2nd_lead_owner_name'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Phone</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['2nd_owner_phone'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['2nd_owner_email'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Address</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['2nd_owner_address'] ?: '-') ?></div>
                        </div>
                        <?php if (!empty($leadData['2nd_owner_other_details'])): ?>
                        <div class="info-row">
                            <div class="info-label">Other Details</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['2nd_owner_other_details'] ?: '-') ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- 3. Professional Information Card -->
            <div class="col-lg-6">
                <div class="info-card">
                    <div class="info-card-header header-pro">
                        <i class="fa-solid fa-briefcase me-2"></i> 3. Professional Information (পেশাগত তথ্য)
                    </div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <div class="info-label">Designation / Profession</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['designation'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">পেশা (বাংলা)</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['profession_bn'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Job Title</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['job_title'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Company Name</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['company_name'] ?? $leadData['company'] ?? '-') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Campaign, Source & System Meta Card -->
            <div class="col-lg-6">
                <div class="info-card">
                    <div class="info-card-header header-meta">
                        <i class="fa-solid fa-bullhorn me-2"></i> 4. Campaign, Source & Status (ক্যাম্পেইন ও সোর্স)
                    </div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <div class="info-label">Campaign & Platform</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['campaign_name'] ?: '-') ?> (<?= htmlspecialchars($leadData['platform'] ?: 'General') ?>)</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Source & Upload Origin</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['source_name'] ?: '-') ?> (<?= htmlspecialchars($leadData['uploaded_from'] ?: '-') ?>)</div>
                        </div>
                        <?php if (!empty($leadData['is_automated']) && (int)$leadData['is_automated'] === 1): ?>
                        <div class="info-row">
                            <div class="info-label">Automated Origin & Approval</div>
                            <div class="info-value">
                                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1"><i class="fa-solid fa-robot me-1"></i>Assigned by System</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 ms-1"><i class="bi bi-patch-check-fill me-1"></i>Approved by: <?= htmlspecialchars(!empty($leadData['approved_by_name']) ? $leadData['approved_by_name'] : 'System') ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <div class="info-label">Assigned Representative</div>
                            <div class="info-value">
                                <?= htmlspecialchars($leadData['user_name'] ?: 'Unassigned') ?>
                                <?php if (!empty($leadData['assigned_by_name'])): ?>
                                    <small class="text-muted ms-2">(Assigned by: <?= htmlspecialchars($leadData['assigned_by_name']) ?>)</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Lead Type & Quality</div>
                            <div class="info-value">
                                <?php
                                $vLt = $leadData['lead_type'] ?: 'Pending';
                                $vLtCls = 'bg-secondary text-white';
                                if (in_array($vLt, ['High Potential', 'High Prospect', 'Hot'])) $vLtCls = 'bg-danger text-white';
                                elseif ($vLt === 'Qualification in Progress') $vLtCls = 'bg-info text-white';
                                elseif (in_array($vLt, ['Potential', 'Prospect', 'Warm'])) $vLtCls = 'bg-warning text-dark';
                                elseif (in_array($vLt, ['Visit Scheduled', 'Will Visit'])) $vLtCls = 'bg-info text-dark';
                                elseif (in_array($vLt, ['Visit Completed', 'Visited'])) $vLtCls = 'bg-primary text-white';
                                elseif (in_array($vLt, ['Not Interested', 'Cold'])) $vLtCls = 'bg-secondary text-white';
                                elseif (in_array($vLt, ['Invalid Lead', 'Irrelevant'])) $vLtCls = 'bg-dark text-white';
                                elseif ($vLt === 'Sold') $vLtCls = 'bg-purple text-white';
                                ?>
                                <span class="badge <?= $vLtCls ?> me-1"><?= htmlspecialchars($vLt) ?></span>
                                <span class="badge bg-secondary"><?= htmlspecialchars($leadData['lead_quality'] ?: 'Pending') ?></span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Last Lead Action / Status</div>
                            <div class="info-value">
                                <span class="badge bg-primary me-1"><?= strtoupper(htmlspecialchars($leadData['last_lead_action'] ?? 'new')) ?></span>
                                <span class="status-badge <?= ($leadData['status'] ?? 'active') === 'active' ? 'status-active' : 'status-inactive' ?>"><?= ucfirst(htmlspecialchars($leadData['status'] ?? 'active')) ?></span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Call Status & Note</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['call_status'] ?: 'not_called') ?> - <em><?= htmlspecialchars($leadData['call_note'] ?: 'No note') ?></em></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Budget / Deal Value</div>
                            <div class="info-value fw-bold text-success"><?= htmlspecialchars($leadData['currency'] ?? 'BDT') ?> <?= number_format((float)($leadData['value_amount'] ?? 0), 2) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Customer Requirements & Preferences Card -->
            <div class="col-lg-6">
                <div class="info-card">
                    <div class="info-card-header header-req">
                        <i class="fa-solid fa-city me-2"></i> 5. Requirements & Preferences (গ্রাহকের চাহিদা)
                    </div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <div class="info-label">Interested Project</div>
                            <div class="info-value fw-bold text-primary"><?= htmlspecialchars($leadData['project_name'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Preferred Location</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['location_name'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Flat / Apartment Size</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['which_apartment_size_are_you_interested_in'] ?? $leadData['pref_flat_size'] ?? '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Floor Choice (Shop / Unit)</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['which_floor_are_you_for_shop'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Planning to Buy Time</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['when_are_you_planning_to_buy'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Site Visit Scheduled?</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['would_you_like_to_schedule_a_site_visit'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Best Contact Time</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['when_is_the_best_time_to_contact_you'] ?? $leadData['preferred_contact_time'] ?? '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Communication Type & Tags</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['communication_type'] ?: '-') ?> | Tags: <em><?= htmlspecialchars($leadData['tags'] ?: 'None') ?></em></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. Nominee Information Card -->
            <div class="col-lg-6">
                <div class="info-card">
                    <div class="info-card-header header-nomi">
                        <i class="fa-solid fa-users me-2"></i> 6. Nominee Information (নমিনি সংক্রান্ত তথ্য)
                    </div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <div class="info-label">Nominee Name</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['nominee_name'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Relation with Applicant</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['relation_with_lead'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Nominee Father & Mother</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['nomi_father_name'] ?: '-') ?> / <?= htmlspecialchars($leadData['nomi_mother_name'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Profession & DOB</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['nomi_profession'] ?: '-') ?> | DOB: <?= htmlspecialchars($leadData['nomi_dob'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Phone & Email</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['nomi_phone'] ?: '-') ?> | <?= htmlspecialchars($leadData['nomi_email'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">NID / Passport</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['nomi_nid_passport'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Nominee Present Address</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['nomi_present_add'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Nominee Permanent Address</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['nomi_permanent_address'] ?: '-') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 7. 2nd Nominee Information Card -->
            <div class="col-lg-6">
                <div class="info-card">
                    <div class="info-card-header header-nomi bg-primary text-white">
                        <i class="fa-solid fa-user-plus me-2"></i> 7. 2nd Nominee Information (২য় নমিনি সংক্রান্ত তথ্য)
                    </div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <div class="info-label">2nd Nominee Name</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['2nd_nominee_name'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Relation with Applicant</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['2nd_relation_with_lead'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">2nd Nominee Father & Mother</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['2nd_nomi_father_name'] ?: '-') ?> / <?= htmlspecialchars($leadData['2nd_nomi_mother_name'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Profession & DOB</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['2nd_nomi_profession'] ?: '-') ?> | DOB: <?= htmlspecialchars($leadData['2nd_nomi_dob'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Phone & Email</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['2nd_nomi_phone'] ?: '-') ?> | <?= htmlspecialchars($leadData['2nd_nomi_email'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">NID / Passport</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['2nd_nomi_nid_passport'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">2nd Nominee Present Address</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['2nd_nomi_present_add'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">2nd Nominee Permanent Address</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['2nd_nomi_permanent_address'] ?: '-') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 8. Description & Additional Notes Card -->
            <div class="col-12">
                <div class="info-card">
                    <div class="info-card-header bg-dark text-white">
                        <i class="fa-solid fa-file-lines me-2"></i> 8. Description & Additional Notes (বিবরণী)
                    </div>
                    <div class="info-card-body">
                        <div style="line-height: 1.8; color: #2c3e50;" class="fs-6">
                            <?= nl2br(htmlspecialchars($leadData['description'] ?: 'No description or extra note provided for this lead.')) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>