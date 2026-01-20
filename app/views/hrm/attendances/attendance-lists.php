<?php
$page_title = "Attendence Page";
$page = 'Attendence';
$show_breadcrumb = true;

// include "../layouts/master.php";
?>


<div class="col-xxl-12">
    <div class="row">
        <div class="col-xxl-6">
            <div class="card__wrapper">

                <a href="<?= $GLOBALS['base_url'] ?>/create-attendance" class="btn btn-primary ">Add Attencence</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-gear"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Employee</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title">850</h3>
                        <span class="card__desc style_two"><span class="price-increase"><i
                                    class="fa-light fa-arrow-up"></i> +5.15%</span> Than Last
                            Month</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-badge-check"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Present</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title">150</h3>
                        <span class="card__desc style_two"><span class="price-decrease"><i
                                    class="fa-light fa-arrow-down"></i> +5.5%</span> Than Last
                            Month</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-user"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Half Day</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title">3130</h3>
                        <span class="card__desc style_two"><span class="price-increase"><i
                                    class="fa-light fa-arrow-up"></i> +10%</span> Than Last
                            Year</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-house-person-leave"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">On Leave Employee</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title">55</h3>
                        <span class="card__desc style_two"><span class="price-increase"><i
                                    class="fa-light fa-arrow-up"></i> +2.15%</span> Than Last
                            Month</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="row g-20 gy-20 mb-20 justify-content-between align-items-end">
                <div class="col-md-12">
                    <div class="d-flex align-items-center">
                        <h6 class="">Note:</h6>
                        <div class="attendant__info-wrapper">
                            <div class="attendant__info-icon">
                                <i class="fa fa-star text-theme"></i>
                                <span class="attachment__info-arrow"><i
                                        class="fa fa-arrow-right text-lightest"></i></span>
                                <h6 class="text-dark small">Holiday</h6>
                            </div>
                            <div class="attendant__info-icon">
                                <i class="fa fa-calendar-week text-secondary"></i>
                                <span class="attachment__info-arrow"><i
                                        class="fa fa-arrow-right text-lightest"></i></span>
                                <h6 class="text-dark small">Day Off</h6>
                            </div>
                            <div class="attendant__info-icon">
                                <i class="fa fa-check text-success"></i>
                                <span class="attachment__info-arrow"><i
                                        class="fa fa-arrow-right text-lightest"></i></span>
                                <h6 class="text-dark small">Present</h6>
                            </div>
                            <div class="attendant__info-icon">
                                <i class="fa fa-star-half-alt text-info"></i>
                                <span class="attachment__info-arrow"><i
                                        class="fa fa-arrow-right text-lightest"></i></span>
                                <h6 class="text-dark small">Half Day</h6>
                            </div>
                            <div class="attendant__info-icon">
                                <i class="fa fa-exclamation-circle text-warning"></i>
                                <span class="attachment__info-arrow"><i
                                        class="fa fa-arrow-right text-lightest"></i></span>
                                <h6 class="text-dark small">Late</h6>
                            </div>
                            <div class="attendant__info-icon">
                                <i class="fa fa-times text-danger"></i>
                                <span class="attachment__info-arrow"><i
                                        class="fa fa-arrow-right text-lightest"></i></span>
                                <h6 class="text-dark small">Absent</h6>
                            </div>
                            <div class="attendant__info-icon">
                                <i class="fa fa-plane-departure text-link"></i>
                                <span class="attachment__info-arrow"><i
                                        class="fa fa-arrow-right text-lightest"></i></span>
                                <h6 class="text-dark small">On Leave</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>