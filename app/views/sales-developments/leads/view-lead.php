<?php
$page = "View Lead";
?>

<style>
    /* Enhanced Styling */
    .lead-view-container {
        min-height: 100vh;
        /* padding: 2rem 0; */
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

    .lead-name-section {
        margin-left: 1.5rem;
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
        transition: transform 0.2s;
    }

    .custom-badge:hover {
        transform: translateY(-2px);
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
        background: #f1eeee;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 1.5rem;
        overflow: hidden;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .info-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
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

    .info-card-body {
        padding: 1.5rem;
    }

    .info-row {
        display: flex;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.2s;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-row:hover {
        background: #f8f9fa;
        padding-left: 0.5rem;
    }

    .info-label {
        flex: 0 0 40%;
        color: #6c757d;
        font-weight: 600;
        font-size: 0.9rem;

    }

    .info-value {
        flex: 1;
        color: #2c3e50;
        font-weight: 500;
    }

    .action-buttons {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .btn-modern {
        padding: 0.6rem 1.5rem;
        border-radius: 25px;
        font-weight: 600;
        border: none;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-primary-modern {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-primary-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    .btn-secondary-modern {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }

    .btn-secondary-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(245, 87, 108, 0.4);
    }

    .followup-card {
        background: #f8f9fa;
        border-left: 4px solid #667eea;
        padding: 1.5rem;
        margin-bottom: 1rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .followup-title {
        color: #2c3e50;
        font-weight: 700;
        font-size: 1.2rem;
        margin-bottom: 0.5rem;
    }

    .followup-subtitle {
        color: #6c757d;
        margin-bottom: 1rem;
    }

    .status-badge {
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .status-active {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
    }

    .status-inactive {
        background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
        color: white;
    }

    .icon-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @media (max-width: 768.98px) {
        .info-label {
            width: 45%;
            min-width: 100px;
            font-size: 13px;
        }

        .info-value {
            width: 55%;
            font-size: 14px;
        }
          .lead-header-card {
        padding: 1.25rem;
        border-radius: 12px;
    }

    .lead-avatar {
        width: 70px;
        height: 70px;
    }

    .lead-name-section {
        margin-left: 0;
    }

    .lead-name-section h2 {
        font-size: 1.1rem;
        line-height: 1.3;
    }

    .lead-name-section p {
        font-size: 0.85rem;
    }

    .badge-container {
        gap: 0.5rem;
        margin-top: 0.75rem;
    }

    .custom-badge {
        font-size: 0.75rem;
        padding: 0.4rem 0.75rem;
    }

    .action-buttons {
        width: 100%;
        display: flex;
        gap: 0.5rem;
    }

    .action-buttons a {
        flex: 1;
        font-size: 0.8rem;
        padding: 0.5rem;
        text-align: center;
    }
        .info-label {
        display: contents;

        font-weight: 500;
        font-size: 0.8rem;
        
    }
    .info-value{
        margin-left: 10px;
    }
    .followup-title{
        font-size: 17px;
    }
    }




</style>

<div class="lead-view-container">
    <div class="container-fluid">

        <!-- Enhanced Header -->
        <div class="lead-header-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-4">

                <!-- Left Section: Avatar + Name + Badges -->
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3 flex-grow-1">

                    <!-- Avatar and Name -->
                    <div class="d-flex align-items-center gap-3">
                        <?php if ($leadData['lead_image']): ?>
                            <img src="<?= getImage('leads', $leadData['lead_image']) ?>"
                                class="lead-avatar"
                                alt="Lead Photo">
                        <?php else: ?>
                            <div class="lead-avatar" style="background: linear-gradient(135deg, #23cfc4 0%, #1e854e 100%); display: flex; align-items: center; justify-content: center;">
                                <i class="fa-light fa-user fa-2x text-white"></i>
                            </div>
                        <?php endif ?>

                        <div class="lead-name-section me-2">
                            <h2 class="mb-0">
                                <?= htmlspecialchars($leadData['full_name'] ?? $leadData['lead_name']) ?>
                            </h2>
                            <p class="text-muted mb-0">
                                <i class="fa-light fa-phone me-1"></i><?= $leadData['phone'] ?>
                            </p>
                        </div>
                    </div>

                    <!-- Badges -->
                    <div class="badge-container">
                        <span class="custom-badge badge-followup">
                            <i class="fa-light fa-calendar-check"></i>
                            Lead Follow Up
                            <?php if (!empty($followUpCounts[$leadData['id']])): ?>
                                <span class="badge bg-danger rounded-pill"><?= $followUpCounts[$leadData['id']] ?></span>
                            <?php endif; ?>
                        </span>

                        <span class="custom-badge badge-booking">
                            <i class="fa-light fa-building"></i>
                            Flat Booking
                            <span class="badge bg-danger rounded-pill">
                                <?= $bookingCount[$leadData['id']] ?? 0 ?>
                            </span>

                        </span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons mt-3">
                    <a href="<?= $GLOBALS['base_url'] ?>/edit-lead/<?= $leadData['id'] ?>" class="btn-modern btn-primary">
                        <i class="fa-light fa-edit"></i> Edit Lead
                    </a>
                    <a href="javascript:history.back()" class="btn-modern btn-secondary">
                        <i class="fa-light fa-arrow-left"></i> Back to List
                    </a>

                </div>
            </div>
        </div>

        <!-- Lead Information Card -->
        <div class="info-card">
            <div class="info-card-header">
                <i class="fa-light fa-user-circle"></i>
                Lead Information
            </div>
            <div class="info-card-body">
                <div class="row">
                    <!-- Left Column -->
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-user me-2"></i>Name</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['full_name'] ?? 'N/A') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-calendar me-2"></i>Date Of Birth</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['dob'] ?? 'N/A') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-id-card me-2"></i>NID/Passport</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['nid_or_passport'] ?? 'N/A') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-briefcase me-2"></i>Profession</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['designation'] ?? 'N/A') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-location-dot me-2"></i>Address</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['address'] ?? '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-home me-2"></i>Permanent Address</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['permanent_address'] ?? '-') ?></div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-user-tie me-2"></i>Father Name</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['father_name'] ?? '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-user-group me-2"></i>Mother Name</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['mother_name'] ?? '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-chart-network me-2"></i>Source</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['source_name'] ?? '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-phone me-2"></i>Phone</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['phone'] ?? 'N/A') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-mobile me-2"></i>Alt Phone</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['alt_phone_number'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-envelope me-2"></i>Email</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['email'] ?: '-') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Nominee Information -->
            <div class="col-lg-6">
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fa-light fa-users"></i>
                        Nominee Information
                    </div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-user me-2"></i>Nominee Name</div>
                            <div class="info-value"><?= $leadData['nominee_name'] ?? 'N/A' ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-user-tie me-2"></i>Father Name</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['nomi_father_name'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-user-group me-2"></i>Mother Name</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['nomi_mother_name'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-heart me-2"></i>Relation</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['relation_with_lead'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-phone me-2"></i>Phone</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['nomi_phone'] ?: '-') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Interest Information -->
            <div class="col-lg-6">
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fa-light fa-building"></i>
                        Interest On Project
                    </div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-city me-2"></i>Project</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['project_type'] ?? 'N/A') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-map-marker-alt me-2"></i>Preferred Location</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['pref_location_id'] ?: '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-ruler-combined me-2"></i>Preferred Size</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['pref_flat_size'] ?: '-') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $groupedFollowUps = [];
        foreach ($followUpProjects as $fu) {
            $projectId = $fu['project_id'] ?? 0;
            if (!isset($groupedFollowUps[$projectId])) {
                $groupedFollowUps[$projectId] = [
                    'project_name' => $fu['project_name'] ?? 'N/A',
                    'flat_name' => $fu['flat_name'] ?? 'N/A',
                    'followups' => [],
                ];
            }
            $groupedFollowUps[$projectId]['followups'][] = $fu;
        }
        ?>

        <!-- Follow Ups Information -->
        <?php if (!empty($followUpProjects)): ?>
            <div class="info-card">
                <div class="info-card-header">
                    <i class="fa-light fa-calendar-check"></i>
                    Follow Ups Information
                </div>
                <div class="info-card-body">
                    <?php if (!empty($groupedFollowUps)): ?>
                        <?php foreach ($groupedFollowUps as $projectId => $group): ?>
                            <div class="followup-card">
                                <h5 class="followup-title">
                                    <i class="fa-light fa-building me-2"></i>
                                    <?= htmlspecialchars($group['project_name']) ?>
                                </h5>
                                <p class="followup-subtitle">
                                    <i class="fa-light fa-home me-2"></i>
                                    Unit: <?= htmlspecialchars($group['flat_name']) ?>
                                </p>

                                <?php foreach ($group['followups'] as $fu): ?>
                                    <div class="row g-2 mb-3 pb-3" style="border-bottom: 2px dashed #dee2e6;">
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <div class="info-label"><i class="fa-light fa-calendar me-2"></i>Date & Time</div>
                                                <div class="info-value"><?= htmlspecialchars($fu['follow_up_date']) ?> <?= htmlspecialchars($fu['follow_up_time']) ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <div class="info-label"><i class="fa-light fa-tag me-2"></i>Type</div>
                                                <div class="info-value"><?= htmlspecialchars($fu['follow_up_type']) ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <div class="info-label"><i class="fa-light fa-circle-check me-2"></i>Status</div>
                                                <div class="info-value"><?= htmlspecialchars($fu['status']) ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <div class="info-label"><i class="fa-light fa-dollar-sign me-2"></i>Share Value</div>
                                                <div class="info-value"><?= htmlspecialchars($fu['price_value']) ?></div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="info-row">
                                                <div class="info-label"><i class="fa-light fa-note me-2"></i>Notes</div>
                                                <div class="info-value"><?= htmlspecialchars($fu['discussion_note'] ?? '-') ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-muted py-4">
                            <i class="fa-light fa-inbox fa-3x mb-3 d-block"></i>
                            No follow-ups found.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Communication Info -->
            <div class="col-lg-6">
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fa-light fa-comments"></i>
                        Communication Details
                    </div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-user-check me-2"></i>Assigned To</div>
                            <div class="info-value"><?= htmlspecialchars($leadData['user_name'] ?? '-') ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-eye me-2"></i>Visibility</div>
                            <div class="info-value">
                                <span class="status-badge" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #2c3e50;">
                                    <?= ucfirst(htmlspecialchars($leadData['visibility'])) ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fa-light fa-circle-info me-2"></i>Status</div>
                            <div class="info-value">
                                <span class="status-badge <?= $leadData['status'] == 'active' ? 'status-active' : 'status-inactive' ?>">
                                    <?= ucfirst(htmlspecialchars($leadData['status'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="col-lg-6">
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fa-light fa-file-lines"></i>
                        Lead Description/Note
                    </div>
                    <div class="info-card-body">
                        <div style="line-height: 1.8; color: #2c3e50;">
                            <?= nl2br(htmlspecialchars($leadData['description'] ?: 'No description provided')) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>