<?php
$page = "Lead & Booking Management";
$show_breadcrumb = true;
?>

<style>
    /* top cards */
    .stat-card {
        /* background: white; */
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    /* campaign card */

    .campaign-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .badge-active {
        background: #d1fae5;
        color: #059669 !important;
    }

    .badge-scheduled {
        background: #fff7ed;
        color: #ea580c !important;
    }

    .badge-completed {
        background: #e0e7ff;
        color: #4338ca !important;
    }

    .badge-failed {
        background: #fee2e2;
        color: #dc2626 !important;
    }

    .btn-light-gray {
        background: #f1f5f9;
    }
</style>



<div class="d-flex justify-content-between mb-4">

    <h4>Analytics</h4>

    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addNewCampaign">
        + Create Campaign
    </button>

</div>
<div class="row mb-4">

    <div class="col-12 col-md-6 col-lg-3 col-xl-3">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Total Campaigns</div>
            <div class="stat-value">12</div>

        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3 col-xl-3">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Active</div>
            <div class="stat-value">12</div>

        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3 col-xl-3">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Scheduled</div>
            <div class="stat-value">7</div>

        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3 col-xl-3">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Avg Success Rate</div>
            <div class="stat-value">90%</div>

        </div>
    </div>

</div>

<div class="row align-items-stretch ">

    <!-- Campaign -->
<?php foreach ($campaigns as $campaign):
    $badgeClass = match($campaign['status']){
        'Active' => 'badge-active',
        'Scheduled' => 'badge-scheduled',
        'Paused' => 'badge-paused',
        'Completed' => 'badge-completed',
        'Failed' => 'badge-failed',
        default => 'badge-scheduled'
    };

    $scheduleDate = date('d/m/Y H:i', strtotime($campaign['schedule_time']));
?>
<div class="col-md-4 d-flex mb-3">
    <div class="card__wrapper w-100 h-100">
        <div class="d-flex justify-content-between">
            <div>
                <h6><?= htmlspecialchars($campaign['name']) ?></h6>
                <small class="text-muted"><?= htmlspecialchars($campaign['project_name'] ?: 'All Leads') ?></small>
            </div>
            <span class="bd-badge <?= $badgeClass ?>"><?= $campaign['status'] ?></span>
        </div>

        <hr>

        <?php if ($campaign['status'] == 'Scheduled'): ?>
            <div class="text-muted">
                Scheduled: <?= $scheduleDate ?>
            </div>
        <?php else: ?>
            <div class="row text-center">
                <div class="col">
                    <small>Sent</small>
                    <h6><?= number_format($campaign['sent_count']) ?></h6>
                </div>
                <div class="col">
                    <small>Delivered</small>
                    <h6 class="text-success"><?= number_format($campaign['delivered_count']) ?></h6>
                </div>
                <div class="col">
                    <small>Success</small>
                    <h6 class="text-primary"><?= $campaign['success_percent'] ?>%</h6>
                </div>
            </div>
        <?php endif; ?>

        <hr>

        <div class="d-flex justify-content-between mt-auto">
            <?php if ($campaign['status'] == 'Active'): ?>
                <button class="btn btn-outline-primary w-75">Pause</button>
            <?php elseif ($campaign['status'] == 'Paused'): ?>
                <button class="btn btn-outline-primary w-75">Resume</button>
            <?php elseif ($campaign['status'] == 'Scheduled'): ?>
                <button class="btn btn-outline-primary w-75">Start Now</button>
            <?php endif; ?>

            <button class="btn btn-link">Report</button>
        </div>
    </div>
</div>
<?php endforeach; ?>


</div>

</div>



<div id="addNewCampaign" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Campaign</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/store-campaign" method="POST">

                    <div class="card__wrapper mb-20">
                        <div class="row g-20 gy-20 align-items-center justify-content-center">

                            <!-- Campaign Name -->
                            <div class="col-lg-12">
                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label for="name">Campaign Name <span>*</span></label>
                                    </div>
                                    <div class="form__input">
                                        <input class="form-control" name="name" id="name" type="text" placeholder="Enter campaign name" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Target Project -->
                            <div class="col-lg-6 col-12">
                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label for="target_project_id">Target Project <span>*</span></label>
                                    </div>
                                    <div class="form__input">
                                        <select class="form-control" name="target_project_id" required>
                                            <option value="">Select Project</option>
                                            <?php foreach ($projects as $project): ?>
                                                <option value="<?= $project['id'] ?>"><?= $project['name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Message Type -->
                            <div class="col-lg-6 col-12">
                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label for="message_type">Message Type <span>*</span></label>
                                    </div>
                                    <div class="form__input">
                                        <select class="form-control" name="message_type" required>
                                            <option value="text">Text</option>
                                            <option value="image">Image</option>
                                            <option value="pdf">PDF/Brochure</option>
                                            <option value="button">Button/CTA</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Message Content -->
                            <div class="col-lg-12">
                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label for="message_content">Message Content <span>*</span></label>
                                    </div>
                                    <textarea class="form-control" name="message_content" placeholder="Enter message here" rows="4" required></textarea>
                                </div>
                            </div>

                            <!-- Schedule -->
                            <div class="col-lg-12 col-12">
                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label for="schedule_time">Schedule <span>*</span></label>
                                    </div>
                                    <div class="form__input">
                                        <input type="datetime-local" class="form-control" name="schedule_time" id="dateTime" required>
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

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const modal = document.getElementById('addEditCampaign');

        // Attach listener only once when modal is shown
        modal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget; // the clicked button


        });
    });
</script>