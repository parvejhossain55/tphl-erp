<?php
$page = "View Lead";
?>

<div class="container-fluid">

    <!-- Header -->
    <!-- Lead Header with Image -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">

        <!-- Left Section: Badges + Image + Name -->
        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3">

            <!-- Badges -->
            <div class="card__wrapper me-sm-3 w-100">
                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                    <!-- Lead Follow Up -->
                    <span class="bd-badge bg-secondary fw-semibold p-2 text-dark d-flex align-items-center gap-1">
                        Lead Follow Up
                        <?php if (!empty($followUpCounts[$leadData['id']])): ?>
                            <span class="badge bg-danger ms-1"><?= $followUpCounts[$leadData['id']] ?></span>
                        <?php endif; ?>
                    </span>

                    <!-- Lead Booking -->
                    <span class="bd-badge bg-secondary fw-semibold p-2 text-dark d-flex align-items-center gap-1">
                        Flat Booking
                        <?php if (!empty($bookingCount[$leadData['id']])): ?>
                            <span class="badge bg-danger ms-1"><?= $bookingCount[$leadData['id']] ?></span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <!-- Lead Image -->
            <div class="d-flex align-items-center gap-2 flex-shrink-0">

                <?php if ($leadData['lead_image']): ?>
                    <div class="employee__thumb">
                        <img src="<?= getImage('leads', $leadData['lead_image']) ?>"
                            class="img-fluid rounded-circle"
                            style="width:80px;height:80px;object-fit:cover;">
                    </div>
                <?php else: ?>
                    <i class="fa-light fa-user-circle fa-3x text-muted"></i>
                <?php endif ?>

                <h4 class="mb-0 lead-name">
                    <?= htmlspecialchars($leadData['lead_name'] ?? 'N/A') ?>
                </h4>
            </div>

        </div>


        <!-- Right Section: Buttons -->
        <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
            <a href="<?= $GLOBALS['base_url'] ?>/edit-lead/<?= $leadData['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
            <a href="<?= $GLOBALS['base_url'] ?>/lead-lists" class="btn btn-secondary btn-sm">← Back</a>
        </div>
    </div>


    <!-- Basic + Contact Info Card -->
    <div class="card__wrapper shadow-sm mb-3">
        <div class="card__header p-2">Lead Information</div>
        <div class="card-body">
            <div class="row">
                <!-- Left Column -->
                <div class="col-md-6">
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Name</div>
                        <div class="col-7 text-title"><?= $leadData['lead_name'] ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Date Of Birth</div>
                        <div class="col-7 text-title"><?= $leadData['dob'] ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">NID/Passport Number</div>
                        <div class="col-7 text-title"><?= $leadData['nid_or_passport'] ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Profession</div>
                        <div class="col-7 text-title"><?= $leadData['designation'] ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Address</div>
                        <div class="col-7 text-title"><?= $leadData['address'] ?? '-' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Permanent Address</div>
                        <div class="col-7 text-title"><?= $leadData['permanent_address'] ?? '-' ?></div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-md-6">
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Father Name</div>
                        <div class="col-7 text-title"><?= $leadData['father_name'] ?? '-' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Mother Name</div>
                        <div class="col-7 text-title"><?= $leadData['mother_name'] ?? '-' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Source</div>
                        <div class="col-7 text-title"><?= $leadData['source_name'] ?? '-' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Phone</div>
                        <div class="col-7 text-title"><?= $leadData['phone'] ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Alt Phone</div>
                        <div class="col-7 text-title"><?= $leadData['alt_phone_number'] ?: '-' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Email</div>
                        <div class="col-7 text-title"><?= $leadData['email'] ?: '-' ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="row">


        <!-- Nominee Info -->
        <div class="col-lg-6">
            <div class="card__wrapper">
                <div class="card__header   p-2 ">Nominee Information</div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Nominee Name</div>
                        <div class="col-7 text-title"><?= $leadData['nominee_name'] ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Father Name</div>
                        <div class="col-7 text-title"><?= $leadData['nomi_father_name'] ?: '-' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Mother Name</div>
                        <div class="col-7 text-title"><?= $leadData['nomi_mother_name'] ?: '-' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Relation With Client</div>
                        <div class="col-7 text-title"><?= $leadData['relation_with_lead'] ?: '-' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Phone</div>
                        <div class="col-7 text-title"><?= $leadData['nomi_phone'] ?: '-' ?></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- interest Info -->
        <div class="col-lg-6">
            <div class="card__wrapper">
                <div class="card__header   p-2 ">Interest On Project</div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Project</div>
                        <div class="col-7 text-title"><?= $leadData['project_type'] ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Preferred Location</div>
                        <div class="col-7 text-title"><?= $leadData['pref_location'] ?: '-' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Preferred Size</div>
                        <div class="col-7 text-title"><?= $leadData['pref_flat_size'] ?: '-' ?></div>
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

    <!-- Project / Interest -->
    <?php if (!empty($followUpProjects)) : ?>

        <div class="col-lg-12">
            <div class="card__wrapper">
                <div class="card__header p-2">Follow Ups Info</div>
                <div class="card-body">
                    <p>Interest on </p>
                    <?php if (!empty($groupedFollowUps)): ?>
                        <?php foreach ($groupedFollowUps as $projectId => $group): ?>
                            <div class="mb-3 p-2 border rounded">
                                <h5 class="mb-2"><?= htmlspecialchars($group['project_name']) ?></h5>
                                <p>Unit: <?= htmlspecialchars($group['flat_name']) ?></p>

                                <?php foreach ($group['followups'] as $fu): ?>
                                    <div class="row mb-1">
                                        <div class="col-4 text-muted">Date & Time</div>
                                        <div class="col-8 text-title"><?= htmlspecialchars($fu['follow_up_date']) ?> <?= htmlspecialchars($fu['follow_up_time']) ?></div>

                                        <div class="col-4 text-muted">Type</div>
                                        <div class="col-8 text-title"><?= htmlspecialchars($fu['follow_up_type']) ?></div>

                                        <div class="col-4 text-muted">Status</div>
                                        <div class="col-8 text-title"><?= htmlspecialchars($fu['status']) ?></div>
                                        <div class="col-4 text-muted">Share Value</div>
                                        <div class="col-8 text-title"><?= htmlspecialchars($fu['price_value']) ?></div>

                                        <div class="col-4 text-muted">Notes</div>
                                        <div class="col-8 text-title"><?= htmlspecialchars($fu['discussion_note'] ?? '-') ?></div>
                                    </div>
                                    <hr>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No follow-ups found.</p>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    <?php endif ?>
    <div class="row">
        <!-- Meta -->
        <div class="col-lg-6">
            <div class="card__wrapper">
                <div class="card__header p-2 ">Communication With</div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Assigned To</div>
                        <div class="col-7 text-title"><?= $leadData['user_name'] ?? '-' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Visibility</div>
                        <div class="col-7 text-title">
                            <span class="badge bg-secondary"><?= ucfirst($leadData['visibility']) ?></span>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Status</div>
                        <div class="col-7 text">
                            <span class="badge bg-<?= $leadData['status'] == 'active' ? 'success' : 'danger' ?>">
                                <?= ucfirst($leadData['status']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="col-6">
            <div class="card__wrapper">
                <div class="card__header   p-2 ">Lead Description/Note</div>
                <div class="card-body text-title">
                    <?= nl2br(htmlspecialchars($leadData['description'] ?: 'No description provided')) ?>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

<style>
    .lead-header h3 {
        font-weight: 600;
    }

    .lead-header .badge {
        font-size: 0.75rem;
        padding: 6px 10px;
        border-radius: 6px;
    }
</style>