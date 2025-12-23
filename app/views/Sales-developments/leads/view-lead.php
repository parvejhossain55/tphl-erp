<?php
$page = "View Lead";
?>

<div class="container-fluid">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1"><?= htmlspecialchars($leadData['lead_name']) ?></h3>
            <span class="badge bg-success"><?= $leadData['lead_type'] ?></span>
           Lead Follow UP <span class="badge bg-info"><?= $leadData['follow_up_count'] ?></span>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= $GLOBALS['base_url'] ?>/edit-lead/<?= $leadData['id'] ?>" class="btn btn-primary btn-sm">
                ✏️ Edit
            </a>
            <a href="<?= $GLOBALS['base_url'] ?>/lead-lists" class="btn btn-secondary btn-sm">
                ← Back
            </a>
        </div>
    </div>

    <div class="row g-4">

        <!-- Basic Info -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light fw-semibold">Basic Information</div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-5 text-muted ">Company</div>
                        <div class="col-7 text-title"><?= $leadData['company'] ?: '-' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Value</div>
                        <div class="col-7 text-title"><?= number_format($leadData['value_amount'] ?? 0) ?> <?= $leadData['currency'] ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Source</div>
                        <div class="col-7 text-title"><?= $leadData['source_name'] ?? '-' ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Info -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light fw-semibold">Contact Information</div>
                <div class="card-body">
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

        <!-- Project / Interest -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light fw-semibold">Interest & Preference</div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Project Type</div>
                        <div class="col-7 text-title"><?= $leadData['project_type'] ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Interested On</div>
                        <div class="col-7 text-title"><?= $leadData['interest_on'] ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Preferred Size</div>
                        <div class="col-7 text-title"><?= $leadData['pref_flat_size'] ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Meta -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light fw-semibold">Meta Information</div>
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
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light fw-semibold">Lead Description</div>
                <div class="card-body text-title">
                    <?= nl2br(htmlspecialchars($leadData['description'] ?: 'No description provided')) ?>
                </div>
            </div>
        </div>

    </div>
</div>
