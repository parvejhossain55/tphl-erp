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
    <div class="col-md-4 d-flex mb-3">
        <div class="card__wrapper  w-100 h-100">

            <div class="d-flex justify-content-between">
                <div>
                    <h6>Marina Heights Launch</h6>
                    <small class="text-muted">Premium Buyers</small>
                </div>

                <span class="bd-badge badge-active">Active</span>
            </div>

            <hr>

            <div class="row text-center">
                <div class="col">
                    <small>Sent</small>
                    <h6>2,500</h6>
                </div>

                <div class="col">
                    <small>Delivered</small>
                    <h6 class="text-success">2,380</h6>
                </div>

                <div class="col">
                    <small>Success</small>
                    <h6 class="text-primary">95.2%</h6>
                </div>
            </div>

            <hr>

            <div class="d-flex justify-content-between mt-auto">
                <button class="btn btn-outline-primary w-75">
                    Pause
                </button>

                <button class="btn btn-link">
                    Report
                </button>
            </div>

        </div>
    </div>

    <!-- Campaign -->
    <div class="col-md-4 d-flex mb-3">
        <div class="card__wrapper w-100 h-100">

            <div class="d-flex justify-content-between">
                <div>
                    <h6>Q1 Offer Reminder</h6>
                    <small class="text-muted">All Leads</small>
                </div>

                <span class="bd-badge badge-scheduled">Scheduled</span>
            </div>

            <hr>

            <div class="text-muted">
                Scheduled: 20/01/2024
            </div>

            <hr>

            <div class="d-flex justify-content-between mt-auto">
                <button class="btn btn-outline-primary w-75">
                    Start Now
                </button>

                <button class="btn btn-link">
                    Report
                </button>
            </div>

        </div>
    </div>

    <!-- Campaign -->
    <div class="col-md-4 d-flex mb-3">
        <div class="card__wrapper w-100 h-100">

            <div class="d-flex justify-content-between">
                <div>
                    <h6>Site Visit Invitation</h6>
                    <small class="text-muted">Investors</small>
                </div>

                <span class="bd-badge badge-completed">Completed</span>
            </div>

            <hr>

            <div class="row text-center">
                <div class="col">
                    <small>Sent</small>
                    <h6>850</h6>
                </div>

                <div class="col">
                    <small>Delivered</small>
                    <h6 class="text-success">820</h6>
                </div>

                <div class="col">
                    <small>Success</small>
                    <h6 class="text-primary">96.5%</h6>
                </div>
            </div>

            <hr>

            <div class="mt-auto">
                <button class="btn btn-link">Report</button>
            </div>

        </div>
    </div>
    <!-- Campaign -->




    <div class="col-md-4 d-flex mb-3">
        <div class="card__wrapper w-100 h-100">

            <div class="d-flex justify-content-between">
                <div>
                    <h6> Payment Reminder</h6>
                    <small class="text-muted">Existing Clients</small>
                </div>

                <span class="bd-badge badge-paused">paused</span>
            </div>

            <hr>

            <div class="row text-center">
                <div class="col">
                    <small>Sent</small>
                    <h6>850</h6>
                </div>

                <div class="col">
                    <small>Delivered</small>
                    <h6 class="text-success">820</h6>
                </div>

                <div class="col">
                    <small>Success</small>
                    <h6 class="text-primary">96.5%</h6>
                </div>
            </div>

            <hr>

            <div class="mt-auto">
                <button class="btn btn-link">Report</button>
            </div>

        </div>
    </div>

    <div class="col-md-4 d-flex mb-3">
        <div class="card__wrapper w-100 h-100">

            <div class="d-flex justify-content-between">
                <div>
                    <h6>New Year Greetings</h6>
                    <small class="text-muted">All Clients</small>
                </div>

                <span class="bd-badge badge-completed">Completed</span>
            </div>

            <hr>

            <div class="row text-center">
                <div class="col">
                    <small>Sent</small>
                    <h6>850</h6>
                </div>

                <div class="col">
                    <small>Delivered</small>
                    <h6 class="text-success">820</h6>
                </div>

                <div class="col">
                    <small>Success</small>
                    <h6 class="text-primary">96.5%</h6>
                </div>
            </div>

            <hr>

            <div class="mt-auto">
                <button class="btn btn-link">Report</button>
            </div>

        </div>
    </div>


    <div class="col-md-4 d-flex mb-3">
        <div class="card__wrapper w-100 h-100">

            <div class="d-flex justify-content-between">
                <div>
                    <h6> Brochure Distribution</h6>
                    <small class="text-muted">NRI Investors</small>
                </div>

                <span class="bd-badge badge-paused">Failed</span>
            </div>

            <hr>

            <div class="row text-center">
                <div class="col">
                    <small>Sent</small>
                    <h6>850</h6>
                </div>

                <div class="col">
                    <small>Delivered</small>
                    <h6 class="text-success">820</h6>
                </div>

                <div class="col">
                    <small>Success</small>
                    <h6 class="text-primary">96.5%</h6>
                </div>
            </div>

            <hr>

            <div class="mt-auto">
                <button class="btn btn-link">Report</button>
            </div>

        </div>
    </div>

</div>

</div>



<div id="addNewCampaign" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New location</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close"><i
                        class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/store-designation" method="POST">

                    <div class="card__wrapper mb-20">
                        <div class="row g-20 gy-20 align-items-center justify-content-center">
                            <div class="col-lg-12">
                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label for="designation">Campaign Name <span>*</span></label>
                                    </div>
                                    <div class="form__input">
                                        <input class="form-control" name="name" id="name" type="text" placeholder="Enter campaign name">
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label for="designation">Target Project <span>*</span></label>
                                    </div>
                                    <div class="form__input">
                                        <select class="form-control" name="department_id">
                                            <option value="">Select Project</option>
                                            <option value="">Project 1</option>
                                            <option value="">Project 2</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label for="designation">Message Type <span>*</span></label>
                                    </div>
                                    <div class="form__input">
                                        <select class="form-control" name="department_id">
                                            <option value="">Select Mesasge </option>
                                            <option value="">Image/</option>
                                            <option value="">PDF/Brocure</option>
                                            <option value="">Button/CTA </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label>Message Content <span>*</span></label>
                                    </div>
                                    <textarea class="form-control" name="approval_note" placeholder="Enter note here" rows="4"></textarea>
                                </div>
                            </div>
                             <div class="col-lg-6 col-12">
                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label for="designation">Schedule <span>*</span></label>
                                    </div>
                                    <div class="form__input">
                                      <input type="datetime" class="form-control" id="dateTime">
                                    </div>
                                </div>
                            </div>
                              <div class="col-lg-6 col-12">
                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label for="designation">Device<span>*</span></label>
                                    </div>
                                    <div class="form__input">
                                        <select class="form-control" name="department_id">
                                            <option value="">Select Mesasge </option>
                                            <option value="">Auto Select</option>
                                        </select>
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
