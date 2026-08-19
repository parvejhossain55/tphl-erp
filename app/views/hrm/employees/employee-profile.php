<?php
$page_title = "View employee ";
$page = 'View Employee';
$show_breadcrumb = true;

?>
<div class="row">

    <div class="col-xxl-7">
        <div class="card__wrapper height-equal">
            <div class="employee__profile-single-box p-relative">
                <div class="card__title-wrap d-flex align-items-center justify-content-between mb-15">
                    <h5 class="card__heading-title">Personal Information</h5>
                    <a data-bs-target="#profile__info" class="edit-icon"
                        href="<?= $GLOBALS['base_url'] ?>/edit-employee/<?= $employee['id'] ?>">
                        <i class="fa-solid fa-pencil"></i>
                    </a>
                </div>
                <div class="profile-view d-flex flex-wrap justify-content-between align-items-start">
                    <div class="d-flex flex-wrap align-items-start gap-20">
                        <div class="profile-img-wrap">
                            <div class="profile-img">
                                <a href="#"><img src="<?= getImage('employees', $employee['photo']) ?>"
                                        alt="User Image"></a>
                            </div>
                        </div>
                        <div class="profile-info">
                            <h4 class="user-name mb-15"><?= $employee['full_name'] ?? '' ?></h4>
                            <h6 class="text-muted mb-5"><?= $employee['department_name'] ?? '' ?></h6>
                            <span class="d-block text-muted mb-5"><?= $employee['designation_name'] ?? '' ?></span>
                            <h6 class="small employee-id text-black mb-5">Employee ID :<?= $employee['employee_id'] ?>
                            </h6>
                            <span class="d-block text-muted ">Date of Join :
                                <?= $employee['joining_date'] ? date('d F, Y', strtotime($employee['joining_date'])) : '' ?></span>
                            <span class="small employee-id text-black mb-5">Gross Salary
                                :<?= $employee['gross_salary'] ?? 0 ?></span>
                            <p><span class="small employee-id text-black mb-5">Employee Type
                                    :<?= ucwords($employee['employee_type'] ?? 'none') ?></span></p>
                            <div class="employee-msg d-none my-3"><a class="btn btn-primary" href="chat.html">Send
                                    Message</a></div>
                        </div>
                    </div>
                    <div class="personal-info-wrapper pr-20">
                        <ul class="list-unstyled">
                            <li class="d-flex mb-2">
                                <div class="fw-bold me-2" style="min-width:130px;">Phone:</div>
                                <div class="text text-link-hover">
                                    <a href="tel:<?= $employee['phone'] ?>"><?= $employee['phone'] ?? '' ?></a>
                                </div>
                            </li>
                            <li class="d-flex mb-2">
                                <div class="fw-bold me-2" style="min-width:130px;">Email:</div>
                                <div class="text text-link-hover">
                                    <a href="mailto:<?= $employee['email'] ?>"><?= $employee['email'] ?></a>
                                </div>
                            </li>
                            <li class="d-flex mb-2">
                                <div class="fw-bold me-2" style="min-width:130px;">Date Of Birth:</div>
                                <div class="text"><?= formatDate($employee['dob']) ?></div>
                            </li>
                            <li class="d-flex mb-2">
                                <div class="fw-bold me-2" style="min-width:130px;">Address:</div>
                                <div class="text"><?= $employee['address'] ?></div>
                            </li>
                            <li class="d-flex mb-2">
                                <div class="fw-bold me-2" style="min-width:130px;">Employee Status:</div>
                                <div class="bd-badge bg-primary"><?= ucwords($employee['employee_status'] ?? 'none') ?>
                                </div>
                            </li>
                            <li class="d-flex mb-2">
                                <div class="fw-bold me-2" style="min-width:130px;">Workplace:</div>
                                <div class="text"><?= $employee['workplace'] ?? '' ?></div>
                            </li>
                            <li class="d-flex mb-2">
                                <div class="fw-bold me-2" style="min-width:130px;">Office Branch:</div>
                                <div class="text">
                                    <?= htmlspecialchars($employee['office_branch_name'] ?? $employee['office_branch'] ?? 'Ashulia Site Office') ?>
                                </div>
                            </li>
                            <li class="d-flex mb-2">
                                <div class="fw-bold me-2" style="min-width:130px;">Report To / Boss:</div>
                                <div class="text">
                                    <?php if (!empty($employee['is_dept_head'])): ?>
                                        <span class="badge bg-success">Department Head</span>
                                    <?php else: ?>
                                        <?= !empty($employee['reporting_boss_name']) ? htmlspecialchars($employee['reporting_boss_name']) : 'N/A' ?>
                                    <?php endif; ?>
                                </div>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-5">
        <div class="card__wrapper height-equal">
            <div class="employee__profile-single-box p-relative">
                <div class="card__title-wrap d-flex align-items-center justify-content-between mb-15">
                    <h5 class="card__heading-title">Emergency Contact</h5>
                    <a href="<?= $GLOBALS['base_url'] ?>/edit-employee/<?= $employee['id'] ?>" class="edit-icon"
                        data-bs-target="#emergency_contact_modal">
                        <i class="fa-solid fa-pencil"></i>
                    </a>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="emergency-contact">
                            <h6 class="card__sub-title mb-10">Primary Contact</h6>
                            <ul class="list-unstyled">
                                <li class="d-flex mb-2">
                                    <div class="fw-bold me-2" style="min-width:130px;">Father Name:</div>
                                    <span><?= $employee['father_name'] ?></span>
                                </li>
                                <li class="d-flex mb-2">
                                    <div class="fw-bold me-2" style="min-width:130px;">Phone:</div>
                                    <span class="text-link-hover">
                                        <a href="tel:<?= $employee['phone'] ?>"><?= $employee['phone'] ?? 'N/A' ?></a>
                                    </span>
                                </li>
                                <li class="d-flex mb-2">
                                    <div class="fw-bold me-2" style="min-width:130px;">Emergency number:</div>
                                    <span class="text-link-hover">

                                        <a
                                            href="tel:<?= $employee['emergency_contact'] ?>"><?= $employee['emergency_contact'] ?? 'N/A' ?></a>
                                    </span>
                                </li>

                                <li class="d-flex mb-2">
                                    <div class="fw-bold me-2" style="min-width:130px;">Address:</div>
                                    <span><?= $employee['address'] ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-6">
        <div class="card__wrapper">
            <div class="employee__profile-single-box p-relative">
                <div class="card__title-wrap d-flex align-items-center justify-content-between mb-15">
                    <h5 class="card__heading-title">Education Qualification</h5>
                    <a href="<?= $GLOBALS['base_url'] ?>/edit-employee/<?= $employee['id'] ?>" class="edit-icon"
                        data-bs-target="#education__info">
                        <i class="fa-solid fa-pencil"></i>
                    </a>
                </div>
                <div class="education__box">
                    <?= $employee['qualification'] ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-6">
        <div class="card__wrapper">
            <div class="employee__profile-single-box p-relative">
                <div class="card__title-wrap d-flex align-items-center justify-content-between mb-15">
                    <h5 class="card__heading-title">Experience Details</h5>
                    <a href="<?= $GLOBALS['base_url'] ?>/edit-employee/<?= $employee['id'] ?>" class="edit-icon"
                        data-bs-target="#experience__info">
                        <i class="fa-solid fa-pencil"></i>
                    </a>
                </div>
                <div class="education__box">
                    <?= $employee['experience'] ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-4">
        <div class="card__wrapper">
            <div class="employee__profile-single-box p-relative">
                <div class="card__title-wrap d-flex align-items-center justify-content-between mb-15">
                    <h5 class="card__heading-title">Bank Account</h5>
                    <a href="<?= $GLOBALS['base_url'] ?>/edit-employee/<?= $employee['id'] ?>" class="edit-icon"
                        data-bs-target="#bank__account__info">
                        <i class="fa-solid fa-pencil"></i>
                    </a>
                </div>
                <div class="personal-info-wrapper bank__account">
                    <ul class="personal-info">
                        <li>
                            <div class="title">Account Holder Name:</div>
                            <div class="text"><?= $employee['account_holder_name'] ?></div>
                        </li>
                        <li>
                            <div class="title">Account Number:</div>
                            <div class="text"><?= $employee['account_number'] ?></div>
                        </li>
                        <li>
                            <div class="title">Bank Name:</div>
                            <div class="text"><?= $employee['bank_name'] ?></div>
                        </li>
                        <li>
                            <div class="title">Branch Name:</div>
                            <div class="text"><?= $employee['branch_name'] ?></div>
                        </li>

                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-4">
        <div class="card__wrapper">
            <div class="employee__profile-single-box p-relative">
                <div class="card__title-wrap d-flex align-items-center justify-content-between mb-15">
                    <h5 class="card__heading-title">Passport Information</h5>
                    <a href="<?= $GLOBALS['base_url'] ?>/edit-employee/<?= $employee['id'] ?>" class="edit-icon"
                        data-bs-target="#passport__info">
                        <i class="fa-solid fa-pencil"></i>
                    </a>
                </div>
                <div class="personal-info-wrapper bank__account">
                    <ul class="personal-info">
                        <li>
                            <div class="title">Passport Number:</div>
                            <div class="text"><?= $employee['pass_num'] ?? 'N/A' ?></div>
                        </li>
                        <li>
                            <div class="title">Nationality:</div>
                            <div class="text">Bangladeshi</div>
                        </li>

                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-4">
        <div class="card__wrapper">
            <div class="employee__profile-single-box p-relative">
                <div class="card__title-wrap d-flex align-items-center justify-content-between mb-15">
                    <h5 class="card__heading-title">Social Profile</h5>
                    <a href="<?= $GLOBALS['base_url'] ?>/edit-employee/<?= $employee['id'] ?>" class="edit-icon"
                        data-bs-target="#social__info">
                        <i class="fa-solid fa-pencil"></i>
                    </a>
                </div>
                <div class="personal-info-wrapper bank__account">
                    <ul class="personal-info">
                        <li>
                            <div class="title">Facebook:</div>
                            <div class="text text-link-hover"><a href="<a href="
                                    <?= formatUrl($employee['social_media1']) ?>>Link</a></div>
                        </li>
                        <li>
                            <div class="title">LinkedIn:</div>
                            <div class="text text-link-hover"><a
                                    href="<?= formatUrl($employee['social_media2']) ?>">Link</a></div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-4">
        <div class="card__wrapper">
            <div class="employee__profile-single-box p-relative">
                <div class="card__title-wrap d-flex align-items-center justify-content-between mb-15">
                    <h5 class="card__heading-title">Disciplinary Action</h5>
                </div>
                <div class="personal-info-wrapper">
                    <ul class="personal-info-list list-unstyled mb-0">
                        <li class="d-flex justify-content-between align-items-center mb-2">
                            <span>Number of Show Cause:</span>
                            <a href="<?= $GLOBALS['base_url'] ?>/generate-letter?type=show_cause&employee_id=<?= $employee['id'] ?>"
                                class="badge bg-danger text-white text-decoration-none" title="View Show Cause Letters">
                                <?= $disciplinary_counts['show_cause'] ?? 0 ?>
                            </a>
                        </li>
                        <li class="d-flex justify-content-between align-items-center mb-2">
                            <span>Number of Warning Letter:</span>
                            <a href="<?= $GLOBALS['base_url'] ?>/generate-letter?type=warning&employee_id=<?= $employee['id'] ?>"
                                class="badge bg-warning text-dark text-decoration-none" title="View Warning Letters">
                                <?= $disciplinary_counts['warning'] ?? 0 ?>
                            </a>
                        </li>
                        <li class="d-flex justify-content-between align-items-center mb-2">
                            <span>Number of Caution Letter:</span>
                            <a href="<?= $GLOBALS['base_url'] ?>/generate-letter?type=caution&employee_id=<?= $employee['id'] ?>"
                                class="badge bg-info text-white text-decoration-none" title="View Caution Letters">
                                <?= $disciplinary_counts['caution'] ?? 0 ?>
                            </a>
                        </li>
                        <li class="d-flex justify-content-between align-items-center">
                            <span>Number of Improvement Letter:</span>
                            <a href="<?= $GLOBALS['base_url'] ?>/generate-letter?type=improvement&employee_id=<?= $employee['id'] ?>"
                                class="badge bg-secondary text-white text-decoration-none"
                                title="View Improvement Letters">
                                <?= $disciplinary_counts['improvement'] ?? 0 ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>