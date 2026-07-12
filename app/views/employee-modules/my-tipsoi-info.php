<style>
/* Color overrides to green #198754 */
.text-primary, .text-primart {
    color: #198754 !important;
}
.bg-primary {
    background-color: #198754 !important;
}
.border-primary {
    border-color: #198754 !important;
}
.btn-primary {
    background-color: #198754 !important;
    border-color: #198754 !important;
}
.btn-primary:hover, .btn-primary:focus {
    background-color: #146c43 !important;
    border-color: #146c43 !important;
}
.list-group-item.active {
    background-color: #198754 !important;
    border-color: #198754 !important;
    color: #ffffff !important;
}
.nav-tabs .nav-link.active {
    color: #198754 !important;
    border-color: #198754 !important;
}
.progress-bar.bg-primary {
    background-color: #198754 !important;
}
.spinner-border.text-primary {
    color: #198754 !important;
}
.border-info {
    border-color: #198754 !important;
}
.border-warning {
    border-color: #198754 !important;
}
.custom-card.border-top {
    border-top-color: #198754 !important;
}
.custom-card.border-top.border-primary {
    border-top-color: #198754 !important;
}
.custom-card.border-top.border-info {
    border-top-color: #198754 !important;
}
.custom-card.border-top.border-warning {
    border-top-color: #198754 !important;
}
.custom-card.border-top.border-success {
    border-top-color: #198754 !important;
}
.custom-card.border-top.border-dark {
    border-top-color: #198754 !important;
}
</style>

        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-bold fs-24 mb-0 text-dark">My Tipsoi Information</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="javascript:void(0);" class="text-muted">Employee</a></li>
                        <li class="breadcrumb-item active text-primary" aria-current="page">Tipsoi Information</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Connection Status Indicator -->
        <div id="tipsoiStatusBanner" class="alert alert-secondary d-flex align-items-center gap-2 mb-4 animate__animated animate__fadeIn" role="alert">
            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
            <span class="fw-semibold">Connecting to Tipsoi API and fetching your records by email...</span>
        </div>

        <!-- Not Registered Warning -->
        <div id="notRegisteredAlert" class="alert alert-danger d-flex align-items-center gap-2 mb-4 animate__animated animate__shakeX d-none" role="alert">
            <i class="fa-solid fa-triangle-exclamation fs-4"></i>
            <div>
                <h6 class="alert-heading fw-bold mb-1">Not Registered in Tipsoi</h6>
                <span id="notRegisteredMsg">This employee email is not registered in the Tipsoi attendance system.</span>
            </div>
        </div>

        <div id="dashboardContent" class="d-none">
            <!-- Navigation Tabs -->
            <div class="card custom-card shadow-sm mb-4">
                <div class="card-header border-bottom-0 pb-0">
                    <ul class="nav nav-tabs card-header-tabs fs-14 fw-semibold border-bottom-0" id="tipsoiTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active text-dark" id="profile-tab" data-bs-toggle="tab" href="#profileTabContent" role="tab" aria-controls="profileTabContent" aria-selected="true">
                                <i class="fa-solid fa-user text-primary me-2"></i>My Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-dark" id="attendance-tab" data-bs-toggle="tab" href="#attendanceTabContent" role="tab" aria-controls="attendanceTabContent" aria-selected="false">
                                <i class="fa-solid fa-calendar-check text-success me-2"></i>Attendance Data
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-dark" id="leave-tab" data-bs-toggle="tab" href="#leaveTabContent" role="tab" aria-controls="leaveTabContent" aria-selected="false">
                                <i class="fa-solid fa-calculator text-info me-2"></i>My Leave Balance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-dark" id="requests-tab" data-bs-toggle="tab" href="#requestsTabContent" role="tab" aria-controls="requestsTabContent" aria-selected="false">
                                <i class="fa-solid fa-hourglass-half text-warning me-2"></i>Pending Requests
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Tab Contents -->
            <div class="tab-content" id="tipsoiTabsContent">
                
                <!-- 1. Profile Tab -->
                <div class="tab-pane fade show active" id="profileTabContent" role="tabpanel" aria-labelledby="profile-tab">
                    <div class="row gy-4">
                        
                        <!-- Left Column: Photo & Sub-navigation -->
                        <div class="col-xl-3 col-lg-4 col-12">
                            <div class="card custom-card border-top border-4 border-primary shadow-sm h-100">
                                <div class="card-body p-4 text-center">
                                    <div class="position-relative d-inline-block mb-4">
                                        <img id="profilePhoto" src="<?= $GLOBALS['base_url'] ?>/public/assets/images/user.jpg" alt="Profile Photo" class="img-thumbnail shadow-xs" style="width: 150px; height: 150px; object-fit: cover;">
                                        <span id="profileStatusBadge" class="position-absolute bottom-0 end-0 badge rounded-pill bg-success p-2 border border-2 border-white" style="width: 18px; height: 18px;"></span>
                                    </div>
                                    
                                    <!-- Profile Sub-Tabs Menu -->
                                    <div class="list-group list-group-flush text-start fs-12 fw-semibold" id="profileSubTabs">
                                        <a href="javascript:void(0);" class="list-group-item list-group-item-action active py-2.5 px-3 border-0 rounded-2 mb-1" data-subtab="general">
                                            <i class="fa-solid fa-circle-info me-2"></i>GENERAL INFORMATION
                                        </a>
                                        <a href="javascript:void(0);" class="list-group-item list-group-item-action py-2.5 px-3 border-0 rounded-2 mb-1 text-muted" data-subtab="approver">
                                            <i class="fa-solid fa-user-shield me-2"></i>APPROVER INFORMATION
                                        </a>
                                        <a href="javascript:void(0);" class="list-group-item list-group-item-action py-2.5 px-3 border-0 rounded-2 mb-1 text-muted" data-subtab="personal">
                                            <i class="fa-solid fa-address-book me-2"></i>PERSONAL & CONTACT INFO
                                        </a>
                                        <a href="javascript:void(0);" class="list-group-item list-group-item-action py-2.5 px-3 border-0 rounded-2 mb-1 text-muted" data-subtab="address">
                                            <i class="fa-solid fa-map-location-dot me-2"></i>ADDRESS
                                        </a>
                                        <a href="javascript:void(0);" class="list-group-item list-group-item-action py-2.5 px-3 border-0 rounded-2 mb-1 text-muted" data-subtab="password">
                                            <i class="fa-solid fa-key me-2"></i>CHANGE PASSWORD
                                        </a>
                                        <a href="javascript:void(0);" class="list-group-item list-group-item-action py-2.5 px-3 border-0 rounded-2 mb-1 text-muted" data-subtab="documents">
                                            <i class="fa-solid fa-file-pdf me-2"></i>DOCUMENTS
                                        </a>
                                        <a href="javascript:void(0);" class="list-group-item list-group-item-action py-2.5 px-3 border-0 rounded-2 mb-1 text-muted" data-subtab="employment">
                                            <i class="fa-solid fa-briefcase me-2"></i>EMPLOYMENT HISTORY
                                        </a>
                                        <a href="javascript:void(0);" class="list-group-item list-group-item-action py-2.5 px-3 border-0 rounded-2 mb-1 text-muted" data-subtab="education">
                                            <i class="fa-solid fa-graduation-cap me-2"></i>EDUCATION HISTORY
                                        </a>
                                        <a href="javascript:void(0);" class="list-group-item list-group-item-action py-2.5 px-3 border-0 rounded-2 mb-1 text-muted" data-subtab="empDocs">
                                            <i class="fa-solid fa-folder-open me-2"></i>EMPLOYEE DOCUMENTS
                                        </a>
                                        <a href="javascript:void(0);" class="list-group-item list-group-item-action py-2.5 px-3 border-0 rounded-2 text-muted" data-subtab="notifications">
                                            <i class="fa-solid fa-bell me-2"></i>NOTIFICATION SETTINGS
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Middle Column: Dynamic Details Table -->
                        <div class="col-xl-9 col-lg-8 col-12">
                            <div class="card custom-card border-top border-4 border-primary shadow-sm h-100">
                                <div class="card-header py-3 border-bottom" style="background-color: #ffffff;">
                                    <h5 class="card-title fw-bold mb-0 text-dark fs-14" id="profileSubTabTitle">GENERAL INFORMATION</h5>
                                </div>
                                <div class="card-body p-4 fs-12">
                                    
                                    <!-- Sub-tab 1: General Information -->
                                    <div id="subtab-general" class="subtab-content">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped align-middle mb-0" style="border-color: #cbd5e1;">
                                                <tbody>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="width: 35%; background-color: #f8fafc; color: #475569;">Name</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pName">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">ID</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pOfficeId">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">PersonID 3rd party Device</td>
                                                        <td class="fw-semibold text-dark ps-3" id="p3rdPartyDevice">Not Set</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Email</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pEmail">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Department</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pDepartment">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Sub Department</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pSubDepartment">Not Set</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Designation</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pDesignation">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Employment Type</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pEmploymentType">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Shift Group</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pShiftGroup">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Gender</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pGender">Male</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Primary Text</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pPrimaryText">Check in</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Secondary Text</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pSecondaryText">Check out</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">RFID</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pRFID">Not Set</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Report Position</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pReportPosition">2</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Leave Policy</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pLeavePolicy">Employee Policy</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Joining Date</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pJoiningDate">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">End Date</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pEndDate">Not Set</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Sub-tab 2: Approver Information -->
                                    <div id="subtab-approver" class="subtab-content d-none">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped align-middle mb-0" style="border-color: #cbd5e1;">
                                                <tbody>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="width: 35%; background-color: #f8fafc; color: #475569;">1st Approver</td>
                                                        <td class="fw-semibold text-dark ps-3" id="p1stApprover">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">2nd Approver</td>
                                                        <td class="fw-semibold text-dark ps-3" id="p2ndApprover">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">1st Late Approver</td>
                                                        <td class="fw-semibold text-dark ps-3" id="p1stLateApprover">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">2nd Late Approver</td>
                                                        <td class="fw-semibold text-dark ps-3" id="p2ndLateApprover">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">1st Manual Entry Approver</td>
                                                        <td class="fw-semibold text-dark ps-3" id="p1stManualApprover">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">2nd Manual Entry Approver</td>
                                                        <td class="fw-semibold text-dark ps-3" id="p2ndManualApprover">—</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Sub-tab 3: Personal & Contact Info -->
                                    <div id="subtab-personal" class="subtab-content d-none">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped align-middle mb-0" style="border-color: #cbd5e1;">
                                                <tbody>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="width: 35%; background-color: #f8fafc; color: #475569;">Father's Name</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pFatherName">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Mother's Name</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pMotherName">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">NID/Passport</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pNID">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Blood Group</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pBloodGroup">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Date of Birth</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pDOB">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Alternative Mobile</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pAltMobile">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Emergency Contact Name</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pEmergName">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Emergency Contact Relation</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pEmergRel">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Emergency Contact Phone</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pEmergPhone">—</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Sub-tab 4: Address -->
                                    <div id="subtab-address" class="subtab-content d-none">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped align-middle mb-0" style="border-color: #cbd5e1;">
                                                <tbody>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="width: 35%; background-color: #f8fafc; color: #475569;">Present Address</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pPresentAddress">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Permanent Address</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pPermanentAddress">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">City</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pCity">—</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted ps-3" style="background-color: #f8fafc; color: #475569;">Country</td>
                                                        <td class="fw-semibold text-dark ps-3" id="pCountry">—</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Sub-tab 5: Change Password -->
                                    <div id="subtab-password" class="subtab-content d-none">
                                        <form id="profileChangePasswordForm">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold text-dark">Old Password</label>
                                                <input type="password" class="form-control form-control-sm" required placeholder="Enter old password">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold text-dark">New Password</label>
                                                <input type="password" class="form-control form-control-sm" required placeholder="Enter new password">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold text-dark">Confirm New Password</label>
                                                <input type="password" class="form-control form-control-sm" required placeholder="Confirm new password">
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-primary">Update Password</button>
                                        </form>
                                    </div>
                                    
                                    <!-- Sub-tab 6: Documents, etc. Placeholders -->
                                    <div id="subtab-placeholders" class="subtab-content d-none text-center py-5">
                                        <i class="fa-regular fa-folder-open fs-1 text-muted mb-3 d-block"></i>
                                        <span class="text-muted fw-semibold">No records found.</span>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>

                        
                    </div>
                </div>

                <!-- 2. Attendance Data Tab -->
                <div class="tab-pane fade" id="attendanceTabContent" role="tabpanel" aria-labelledby="attendance-tab">
                    <div class="card custom-card border-top border-4 border-success shadow-sm mb-4">
                        <div class="card-header py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-3" style="background-color: #ffffff;">
                            <h5 class="card-title fw-bold mb-0 text-success fs-15" id="attendanceHeaderTitle" style="letter-spacing: 0.5px;">
                                INDIVIDUAL ATTENDANCE REPORT
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <!-- Employee Info Bar & Filters -->
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-4 mb-4 pb-3 border-bottom">
                                <!-- Employee Profile Left -->
                                <div class="d-flex align-items-center gap-3">
                                    <img id="infoCardPhoto" src="<?= $GLOBALS['base_url'] ?>/assets/images/user.jpg" alt="Profile" class="rounded-circle img-thumbnail shadow-xs" style="width: 60px; height: 60px; object-fit: cover;">
                                    <div>
                                        <h6 class="fw-bold mb-1 text-dark fs-14" id="infoCardName">—</h6>
                                        <div class="text-muted fs-11 mb-1" id="infoCardDesignation">—</div>
                                        <div class="badge bg-secondary-subtle text-secondary fs-9" id="infoCardOT" style="padding: 3px 8px; border-radius: 4px;">Overtime Not Allowed</div>
                                    </div>
                                </div>
                                
                                <!-- Search & Filter Controls Middle/Right -->
                                <div class="d-flex align-items-center flex-wrap gap-3 ms-auto">
                                    <!-- Search Box -->
                                    <div class="position-relative">
                                        <input type="text" id="infoCardSearchName" class="form-control form-control-sm ps-3 pe-5 bg-white border" readonly style="width: 170px; font-size: 12px; height: 32px;" value="—">
                                        <i class="fa-solid fa-search position-absolute top-50 end-0 translate-middle-y me-3 text-muted fs-12"></i>
                                    </div>
                                    
                                    <!-- Month Dropdown Selector -->
                                    <div class="position-relative">
                                        <input type="month" id="monthFilter" class="form-control form-control-sm ps-3 pe-4 bg-white border" value="<?= date('Y-m') ?>" style="width: 160px; font-size: 12px; height: 32px;">
                                    </div>
                                    
                                    <!-- Rows Per Page -->
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted fs-12 fw-semibold">Rows Per Page</span>
                                        <select id="rowsPerPage" class="form-select form-select-sm border bg-white" style="width: 65px; font-size: 12px; height: 32px;">
                                            <option value="31" selected>31</option>
                                            <option value="15">15</option>
                                            <option value="10">10</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Export Buttons -->
                                    <div class="d-flex gap-2">
                                        <button id="btnMyAttendanceExcel" class="btn btn-sm text-white px-3 d-flex align-items-center gap-2 fw-semibold fs-11" style="background-color: #1b5e20; border-color: #1b5e20; height: 32px;">
                                            <i class="fa-solid fa-file-excel"></i> Excel
                                        </button>
                                        <button id="btnMyAttendancePdf" class="btn btn-sm text-white px-3 d-flex align-items-center gap-2 fw-semibold fs-11" style="background-color: #b71c1c; border-color: #b71c1c; height: 32px;">
                                            <i class="fa-solid fa-file-pdf"></i> PDF
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- 2-Row Professional Summary Table Grid -->
                            <div class="table-responsive mb-4 shadow-xs rounded border">
                                <table class="table table-bordered table-sm text-center align-middle mb-0 fs-12" style="border-color: #cbd5e1; min-width: 900px;">
                                    <tbody>
                                        <tr>
                                            <td class="fw-semibold text-muted text-start ps-3" style="width: 13%; background-color: #f8fafc; color: #475569;">Working Day</td>
                                            <td class="fw-bold text-dark" style="width: 4%; background-color: #ffffff;" id="statWorkingDays">0</td>
                                            <td class="fw-semibold text-muted text-start ps-3" style="width: 13%; background-color: #f8fafc; color: #475569;">Weekend/Day Off</td>
                                            <td class="fw-bold text-dark" style="width: 4%; background-color: #ffffff;" id="statWeekendOff">0</td>
                                            <td class="fw-semibold text-muted text-start ps-3" style="width: 13%; background-color: #f8fafc; color: #475569;">Leave</td>
                                            <td class="fw-bold text-dark" style="width: 4%; background-color: #ffffff;" id="statLeave">0</td>
                                            <td class="fw-semibold text-muted text-start ps-3" style="width: 13%; background-color: #f8fafc; color: #475569;">Late</td>
                                            <td class="fw-bold text-dark" style="width: 4%; background-color: #ffffff;" id="statLate">0</td>
                                            <td class="fw-semibold text-muted text-start ps-3" style="width: 13%; background-color: #f8fafc; color: #475569;">Field Visit/Tours</td>
                                            <td class="fw-bold text-dark" style="width: 4%; background-color: #ffffff;" id="statFieldVisit">0</td>
                                            <td class="fw-semibold text-muted text-start ps-3" style="width: 13%; background-color: #f8fafc; color: #475569;">Actual Hours</td>
                                            <td class="fw-bold text-dark" style="width: 7%; background-color: #ffffff;" id="statActualHours">0h 0m</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted text-start ps-3" style="background-color: #f8fafc; color: #475569;">Present</td>
                                            <td class="fw-bold text-dark" style="background-color: #ffffff;" id="statPresent">0</td>
                                            <td class="fw-semibold text-muted text-start ps-3" style="background-color: #f8fafc; color: #475569;">Govt. Holiday</td>
                                            <td class="fw-bold text-dark" style="background-color: #ffffff;" id="statGovtHoliday">0</td>
                                            <td class="fw-semibold text-muted text-start ps-3" style="background-color: #f8fafc; color: #475569;">Special Days</td>
                                            <td class="fw-bold text-dark" style="background-color: #ffffff;" id="statSpecialDays">0</td>
                                            <td class="fw-semibold text-muted text-start ps-3" style="background-color: #f8fafc; color: #475569;">Absent</td>
                                            <td class="fw-bold text-dark" style="background-color: #ffffff;" id="statAbsent">0</td>
                                            <td class="fw-semibold text-muted text-start ps-3" style="background-color: #f8fafc; color: #475569;">Early Exit</td>
                                            <td class="fw-bold text-dark" style="background-color: #ffffff;" id="statEarlyExit">0</td>
                                            <td class="fw-semibold text-muted text-start ps-3" style="background-color: #f8fafc; color: #475569;">Expected Hours</td>
                                            <td class="fw-bold text-dark" style="background-color: #ffffff;" id="statExpectedHours">0h 0m</td>
                                            <td class="fw-semibold text-muted text-start ps-3" style="background-color: #f8fafc; color: #475569;">Average Hours</td>
                                            <td class="fw-bold text-dark" style="background-color: #ffffff;" id="statAverageHours">0h 0m</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Daily Attendance Logs Table -->
                            <div class="table-responsive shadow-sm rounded border">
                                <table class="table table-bordered table-striped table-hover text-center align-middle mb-0 fs-12" style="min-width: 900px;">
                                    <thead class="text-white" style="background-color: #00A896;">
                                        <tr>
                                            <th class="py-3 text-start ps-3">Date</th>
                                            <th class="py-3">Shift</th>
                                            <th class="py-3">Status</th>
                                            <th class="py-3">Entry</th>
                                            <th class="py-3">Exit</th>
                                            <th class="py-3">Late</th>
                                            <th class="py-3">Early Exit</th>
                                            <th class="py-3">Working Hours</th>
                                            <th class="py-3">OT Hours</th>
                                        </tr>
                                    </thead>
                                    <tbody id="attendanceTableBody">
                                        <!-- Dynamic Rows -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Leave Balance Tab -->
                <div class="tab-pane fade" id="leaveTabContent" role="tabpanel" aria-labelledby="leave-tab">
                    <div class="row">
                        <!-- Leave Balances -->
                        <div class="col-xl-7 col-12">
                            <div class="card custom-card border-top border-4 border-info shadow-sm">
                                <div class="card-header">
                                    <h5 class="card-title fw-bold mb-0 text-dark"><i class="fa-solid fa-calculator text-info me-2"></i>My Leave Balances (Current Year)</h5>
                                </div>
                                <div class="card-body p-4">
                                    <div id="leaveBalanceContainer" class="row gy-4">
                                        <!-- Dynamic Leave Balance Cards -->
                                    </div>
                                    <div id="noLeavesMsg" class="text-muted text-center py-4 d-none">
                                        <i class="fa-regular fa-folder-open fs-2 mb-2 d-block text-secondary"></i>
                                        No leave balance records found in Tipsoi.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Leave History -->
                        <div class="col-xl-5 col-12">
                            <div class="card custom-card border-top border-4 border-dark shadow-sm">
                                <div class="card-header">
                                    <h5 class="card-title fw-bold mb-0 text-dark"><i class="fa-solid fa-calendar-minus text-dark me-2"></i>Leave History Log</h5>
                                </div>
                                <div class="card-body p-4">
                                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                        <table class="table table-sm table-striped table-bordered text-start fs-12 mb-0">
                                            <thead style="background-color: #f8fafc; color: #475569;">
                                                <tr>
                                                    <th>Leave Type</th>
                                                    <th>Dates</th>
                                                    <th>Duration</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody id="leaveHistoryTableBody">
                                                <!-- Dynamic Rows -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <div id="noLeaveHistoryMsg" class="text-muted text-center py-4 d-none">
                                        <i class="fa-regular fa-folder-open fs-2 mb-2 d-block text-secondary"></i>
                                        No recent leave history records found.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Pending Requests Tab -->
                <div class="tab-pane fade" id="requestsTabContent" role="tabpanel" aria-labelledby="requests-tab">
                    <div class="card custom-card border-top border-4 border-warning shadow-sm">
                        <div class="card-header">
                            <h5 class="card-title fw-bold mb-0 text-dark"><i class="fa-solid fa-hourglass text-warning me-2"></i>My Pending Approvals</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered text-start fs-12 mb-0">
                                    <thead style="background-color: #f8fafc; color: #475569;">
                                        <tr>
                                            <th>Request Type</th>
                                            <th>Requested Date</th>
                                            <th>Reason</th>
                                            <th>Approval status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="pendingRequestsTableBody">
                                        <!-- Dynamic Rows -->
                                    </tbody>
                                </table>
                            </div>
                            <div id="noPendingRequestsMsg" class="text-muted text-center py-4 d-none">
                                <i class="fa-regular fa-circle-check fs-2 mb-2 d-block text-success animate__animated animate__pulse animate__infinite"></i>
                                No pending requests found. All clear!
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Safely hide the global preloader if it gets stuck due to asset load race conditions
    const preloader = document.getElementById("preloader");
    if (preloader) {
        preloader.style.display = "none";
    }

    // Profile Sub-tabs Switcher Logic
    const profileSubTabs = document.getElementById("profileSubTabs");
    if (profileSubTabs) {
        profileSubTabs.querySelectorAll("a[data-subtab]").forEach(link => {
            link.addEventListener("click", function(e) {
                e.preventDefault();
                
                // Toggle active classes
                profileSubTabs.querySelectorAll("a").forEach(l => {
                    l.classList.remove("active");
                    l.classList.add("text-muted");
                });
                this.classList.add("active");
                this.classList.remove("text-muted");
                
                // Update title
                const subtabName = this.getAttribute("data-subtab");
                const cleanTitle = this.textContent.trim();
                document.getElementById("profileSubTabTitle").textContent = cleanTitle;
                
                // Hide all contents
                document.querySelectorAll(".subtab-content").forEach(content => {
                    content.classList.add("d-none");
                });
                
                // Show target content
                const targetContent = document.getElementById(`subtab-${subtabName}`);
                if (targetContent) {
                    targetContent.classList.remove("d-none");
                } else {
                    document.getElementById("subtab-placeholders").classList.remove("d-none");
                }
            });
        });
    }

    const monthFilter = document.getElementById("monthFilter");
    
    // Initial Load
    fetchTipsoiDashboard(monthFilter.value);

    // Filter Change Listener
    monthFilter.addEventListener("change", function() {
        fetchTipsoiDashboard(this.value);
    });

    function fetchTipsoiDashboard(month) {
        const banner = document.getElementById("tipsoiStatusBanner");
        const warning = document.getElementById("notRegisteredAlert");
        const content = document.getElementById("dashboardContent");
        
        banner.classList.remove("d-none");
        warning.classList.add("d-none");
        content.classList.add("d-none");

        fetch(`<?= $GLOBALS['base_url'] ?>/api/employee/tipsoi-data?month=${month}`)
            .then(res => res.json())
            .then(response => {
                banner.classList.add("d-none");
                if (!response.success) {
                    warning.classList.remove("d-none");
                    document.getElementById("notRegisteredMsg").textContent = response.message || "Failed to fetch Tipsoi records.";
                    return;
                }

                if (!response.isRegistered) {
                    warning.classList.remove("d-none");
                    document.getElementById("notRegisteredMsg").textContent = response.message;
                    return;
                }

                content.classList.remove("d-none");

                // 1. Populate Profile Details
                const profile = response.tipsoiProfile;
                const erp = response.erpProfile || {};

                // General Information
                document.getElementById("pName").textContent = profile.employeeName || erp.full_name || '—';
                document.getElementById("pOfficeId").textContent = profile.employeeOfficeId || erp.employee_id || '—';
                document.getElementById("p3rdPartyDevice").textContent = profile.personId3rdPartyDevice || 'Not Set';
                document.getElementById("pEmail").textContent = profile.email || erp.email || '—';
                document.getElementById("pDepartment").textContent = profile.departmentName || erp.department_name || '—';
                document.getElementById("pSubDepartment").textContent = profile.subDepartmentName || 'Not Set';
                document.getElementById("pDesignation").textContent = profile.designationName || erp.designation_name || '—';
                document.getElementById("pEmploymentType").textContent = profile.employmentTypeName || '—';
                document.getElementById("pShiftGroup").textContent = profile.shiftGroupName || '—';
                document.getElementById("pGender").textContent = profile.gender || erp.gender || 'Male';
                document.getElementById("pPrimaryText").textContent = profile.primaryDisplayText || 'Check in';
                document.getElementById("pSecondaryText").textContent = profile.secondaryDisplayText || 'Check out';
                document.getElementById("pRFID").textContent = profile.rfid || erp.rfid_card_no || 'Not Set';
                document.getElementById("pReportPosition").textContent = profile.reportPosition || '2';
                document.getElementById("pLeavePolicy").textContent = profile.leavePolicyName || 'Employee Policy';
                document.getElementById("pJoiningDate").textContent = profile.joiningDate ? formatDate(profile.joiningDate) : (erp.joining_date ? erp.joining_date : '—');
                document.getElementById("pEndDate").textContent = profile.endDate || 'Not Set';

                // Approver Information
                document.getElementById("p1stApprover").textContent = profile.firstApproverName || '—';
                document.getElementById("p2ndApprover").textContent = profile.secondApproverName || '—';
                document.getElementById("p1stLateApprover").textContent = profile.firstLateApproverName || '—';
                document.getElementById("p2ndLateApprover").textContent = profile.secondLateApproverName || '—';
                document.getElementById("p1stManualApprover").textContent = profile.firstManualEntryApproverName || '—';
                document.getElementById("p2ndManualApprover").textContent = profile.secondManualEntryApproverName || '—';

                // Personal & Contact Info
                document.getElementById("pFatherName").textContent = erp.father_name || '—';
                document.getElementById("pMotherName").textContent = erp.mother_name || '—';
                document.getElementById("pNID").textContent = erp.nid_or_passport || erp.nid || '—';
                document.getElementById("pBloodGroup").textContent = erp.blood_group || '—';
                document.getElementById("pDOB").textContent = erp.date_of_birth || '—';
                document.getElementById("pAltMobile").textContent = erp.alternative_mobile || '—';
                document.getElementById("pEmergName").textContent = erp.emergency_contact_name || erp.emergency_contact_person || '—';
                document.getElementById("pEmergRel").textContent = erp.emergency_contact_relation || '—';
                document.getElementById("pEmergPhone").textContent = erp.emergency_contact_number || erp.emergency_contact_phone || '—';

                // Address
                document.getElementById("pPresentAddress").textContent = erp.present_address || '—';
                document.getElementById("pPermanentAddress").textContent = erp.permanent_address || '—';
                document.getElementById("pCity").textContent = erp.city || '—';
                document.getElementById("pCountry").textContent = erp.country || '—';

                // Attendance Exclude Checkbox
                const excludeChk = document.getElementById("excludeAttendanceCheckbox");
                if (excludeChk) {
                    excludeChk.checked = (profile.includeInAttendanceReport == 0);
                }

                if (profile.photo) {
                    document.getElementById("profilePhoto").src = profile.photo;
                } else {
                    document.getElementById("profilePhoto").src = "<?= $GLOBALS['base_url'] ?>/public/assets/images/user.jpg";
                }

                const statusBadge = document.getElementById("profileStatusBadge");
                if (profile.status == 1) {
                    statusBadge.className = "position-absolute bottom-0 end-0 badge rounded-pill bg-success p-2 border border-2 border-white";
                } else {
                    statusBadge.className = "position-absolute bottom-0 end-0 badge rounded-pill bg-danger p-2 border border-2 border-white";
                }

                // 2. Populate Leave Balances
                const leaveContainer = document.getElementById("leaveBalanceContainer");
                const noLeavesMsg = document.getElementById("noLeavesMsg");
                leaveContainer.innerHTML = "";
                
                const balances = response.leaveBalances;
                if (!balances || balances.length === 0) {
                    noLeavesMsg.classList.remove("d-none");
                } else {
                    noLeavesMsg.classList.add("d-none");
                    balances.forEach(b => {
                        const total = parseInt(b.entitled !== undefined ? b.entitled : (b.allocation || b.totalDays || 0));
                        const remaining = parseInt(b.balance !== null ? b.balance : total);
                        const used = parseInt(b.used !== undefined ? b.used : Math.max(0, total - remaining));
                        const pct = total > 0 ? Math.round((used / total) * 100) : 0;
                        
                        let colorClass = "bg-primary";
                        let borderClass = "border-primary";
                        const nameLower = String(b.leaveCategoryName).toLowerCase();
                        if (nameLower.includes("casual")) {
                            colorClass = "bg-success";
                            borderClass = "border-success";
                        } else if (nameLower.includes("sick")) {
                            colorClass = "bg-warning";
                            borderClass = "border-warning";
                        } else if (nameLower.includes("earn") || nameLower.includes("annual")) {
                            colorClass = "bg-info";
                            borderClass = "border-info";
                        } else if (nameLower.includes("without") || nameLower.includes("lwp")) {
                            colorClass = "bg-danger";
                            borderClass = "border-danger";
                        }

                        leaveContainer.innerHTML += `
                            <div class="col-md-6 col-12 animate__animated animate__fadeInUp">
                                <div class="p-3 border border-start border-4 ${borderClass} rounded-3 bg-white shadow-sm">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-bold text-dark fs-13">${b.leaveCategoryName}</span>
                                        <span class="text-muted fs-12 fw-semibold">${remaining} Days Left</span>
                                    </div>
                                    <div class="progress progress-xs mb-2">
                                        <div class="progress-bar ${colorClass}" role="progressbar" style="width: ${pct}%" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted fs-11">
                                        <span>Entitled: <strong>${total}</strong></span>
                                        <span>Used: <strong>${used}</strong></span>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }

                // 3. Populate Attendance Logs & Stats
                const tbody = document.getElementById("attendanceTableBody");
                tbody.innerHTML = "";
                
                const attendance = response.attendance || [];
                const logsMap = {};
                attendance.forEach(log => {
                    if (log.date) {
                        const cleanDate = log.date.split(" ")[0]; // e.g. "01.07.2026"
                        logsMap[cleanDate] = log;
                    }
                });

                // Update Attendance Title with Employee Name and Month
                const monthInputVal = document.getElementById("monthFilter").value;
                let displayMonthYear = "";
                if (monthInputVal) {
                    const parts = monthInputVal.split("-");
                    const dateObj = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, 1);
                    displayMonthYear = ", " + dateObj.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }).toUpperCase();
                }
                document.getElementById("attendanceHeaderTitle").textContent = "INDIVIDUAL ATTENDANCE REPORT OF " + (profile.employeeName || '—').toUpperCase() + displayMonthYear;

                // Populate Attendance Info Card details
                document.getElementById("infoCardName").textContent = profile.employeeName || '—';
                document.getElementById("infoCardDesignation").textContent = (profile.designationName || 'N/A') + " (Department: " + (profile.departmentName || 'N/A') + ")";
                document.getElementById("infoCardSearchName").value = profile.employeeName || '—';

                if (response.dates) {
                    response.dates.forEach(d => {
                        // d is e.g. "01.07.2026 (Wed)"
                        const cleanD = d.split(" ")[0]; // "01.07.2026"
                        const dayName = d.split(" ").slice(1).join(" ") || "";
                        
                        const parts = cleanD.split(".");
                        let formattedDateStr = cleanD;
                        let isWeekend = false;
                        if (parts.length === 3) {
                            const day = parseInt(parts[0], 10);
                            const month = parseInt(parts[1], 10) - 1;
                            const year = parseInt(parts[2], 10);
                            const dt = new Date(year, month, day);
                            formattedDateStr = dt.toLocaleDateString('en-US', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
                            
                            const dayOfWeek = dt.getDay();
                            if (dayOfWeek === 5 || dayOfWeek === 6) {
                                isWeekend = true;
                            }
                        }
                        
                        let shiftName = "—";
                        let statusText = "Absent";
                        let statusClass = "text-danger fw-semibold";
                        let entryStr = "—";
                        let exitStr = "—";
                        let lateStr = "—";
                        let earlyStr = "—";
                        let workingStr = "—";
                        let otStr = "—";

                        const log = logsMap[cleanD];
                        if (log) {
                            const shiftGroup = log.shiftGroupName || "Regular";
                            const timeRange = log.shiftTimeRange || "09:30 AM-06:30 PM";
                            shiftName = `${shiftGroup}<br><small class="text-muted">${timeRange}</small>`;
                            
                            if (log.isPresent == 1) {
                                statusText = "Present";
                                statusClass = "text-dark";
                            } else if (log.isLeave == 1 || log.leaveAppliedButNotApproved == 1) {
                                statusText = log.leaveAppliedButNotApproved == 1 ? "Leave Approval Pending" : (log.leaveCategoryName || "Leave");
                                statusClass = log.leaveAppliedButNotApproved == 1 ? "text-warning fw-semibold" : "text-info fw-semibold";
                            } else if (log.isOffday == 1) {
                                statusText = "Off Day";
                                statusClass = "text-success fw-semibold";
                            } else if (log.isHoliday == 1) {
                                statusText = "Govt. Holiday";
                                statusClass = "text-primary fw-semibold";
                            } else {
                                statusText = "Absent";
                                statusClass = "text-danger fw-semibold";
                            }

                            entryStr = log.firstLoggedTime || "—";
                            exitStr = log.lastLoggedTime || "—";

                            if (log.lateBy && log.lateBy !== "-") {
                                lateStr = log.lateBy;
                            }
                            if (log.earlyExitBy && log.earlyExitBy !== "-") {
                                earlyStr = log.earlyExitBy;
                            }

                            workingStr = log.totalHour || "—";
                            
                            if (log.overtimeInMinutes && parseInt(log.overtimeInMinutes) > 0) {
                                const otMins = parseInt(log.overtimeInMinutes);
                                const otH = Math.floor(otMins / 60);
                                const otM = otMins % 60;
                                otStr = otH > 0 ? `${otH}h ${otM}m` : `${otM}m`;
                            }
                        } else {
                            if (isWeekend) {
                                statusText = "Off Day";
                                statusClass = "text-success fw-semibold";
                            } else {
                                statusText = "—";
                                statusClass = "text-muted";
                            }
                        }

                        tbody.innerHTML += `
                            <tr>
                                <td class="fw-semibold text-dark text-start ps-3">${formattedDateStr}</td>
                                <td>${shiftName}</td>
                                <td><span class="${statusClass}">${statusText}</span></td>
                                <td class="fw-bold text-success">${entryStr}</td>
                                <td class="fw-bold text-danger">${exitStr}</td>
                                <td class="text-warning">${lateStr}</td>
                                <td>${earlyStr}</td>
                                <td class="fw-bold text-primary">${workingStr}</td>
                                <td>${otStr}</td>
                            </tr>
                        `;
                    });
                }

                // Update 2-Row Professional Summary Table Grid Stats
                const summary = response.attendanceSummary || {};
                
                // Row 1
                document.getElementById("statWorkingDays").textContent = response.dates ? response.dates.length : 0;
                document.getElementById("statWeekendOff").textContent = summary.totalWeeklyOffdays !== undefined ? summary.totalWeeklyOffdays : 0;
                document.getElementById("statLeave").textContent = summary.totalLeave !== undefined ? summary.totalLeave : 0;
                document.getElementById("statLate").textContent = summary.totalLateEntry !== undefined ? summary.totalLateEntry : 0;
                document.getElementById("statFieldVisit").textContent = summary.totalFieldVisits !== undefined ? summary.totalFieldVisits : 0;
                document.getElementById("statActualHours").textContent = summary.totalWorkingHour || '0h 0m';
                
                // Row 2
                document.getElementById("statPresent").textContent = summary.totalPresent !== undefined ? summary.totalPresent : 0;
                document.getElementById("statGovtHoliday").textContent = summary.totalHolidays !== undefined ? summary.totalHolidays : 0;
                document.getElementById("statSpecialDays").textContent = summary.totalSpecialDays !== undefined ? summary.totalSpecialDays : 0;
                document.getElementById("statAbsent").textContent = summary.totalAbsent !== undefined ? summary.totalAbsent : 0;
                document.getElementById("statEarlyExit").textContent = summary.totalEarlyExit !== undefined ? summary.totalEarlyExit : 0;
                document.getElementById("statExpectedHours").textContent = summary.totalExpectedWorkingHour || '0h 0m';
                document.getElementById("statAverageHours").textContent = summary.averageWorkingHoursAndMinutes || '0h 0m';

                // 4. Populate Pending Requests
                const pendingTbody = document.getElementById("pendingRequestsTableBody");
                const noPendingMsg = document.getElementById("noPendingRequestsMsg");
                pendingTbody.innerHTML = "";
                
                const pending = response.pendingRequests || [];
                if (pending.length === 0) {
                    noPendingMsg.classList.remove("d-none");
                } else {
                    noPendingMsg.classList.add("d-none");
                    pending.forEach(p => {
                        let statusText = "Pending Approval";
                        let statusBadge = "bg-warning-subtle text-warning";
                        if (p.status == 1) {
                            statusText = "Approved";
                            statusBadge = "bg-success-subtle text-success";
                        } else if (p.status == 2) {
                            statusText = "Rejected";
                            statusBadge = "bg-danger-subtle text-danger";
                        }

                        let approverStr = "";
                        if (p.firstApprover) {
                            approverStr += `<div>1st: ${p.firstApprover} <span class="badge ${p.firstApprovalStatus == 1 ? 'bg-success' : 'bg-warning'}">${p.firstApprovalStatus == 1 ? 'Appr.' : 'Pend.'}</span></div>`;
                        }
                        if (p.secondApprover) {
                            approverStr += `<div>2nd: ${p.secondApprover} <span class="badge ${p.secondApprovalStatus == 1 ? 'bg-success' : 'bg-warning'}">${p.secondApprovalStatus == 1 ? 'Appr.' : 'Pend.'}</span></div>`;
                        }

                        pendingTbody.innerHTML += `
                            <tr>
                                <td class="fw-bold text-dark">${p.requestType}</td>
                                <td>${p.startDate}</td>
                                <td>${p.reason}</td>
                                <td>
                                    ${approverStr || `<span class="badge ${statusBadge}">${statusText}</span>`}
                                </td>
                            </tr>
                        `;
                    });
                }

                // 5. Populate Leave History
                const leaveHistoryTbody = document.getElementById("leaveHistoryTableBody");
                const noLeaveHistMsg = document.getElementById("noLeaveHistoryMsg");
                leaveHistoryTbody.innerHTML = "";
                
                const history = response.leaveHistory || [];
                if (history.length === 0) {
                    noLeaveHistMsg.classList.remove("d-none");
                } else {
                    noLeaveHistMsg.classList.add("d-none");
                    history.slice(0, 10).forEach(h => {
                        let statusText = "Pending";
                        let statusBadge = "bg-warning";
                        if (h.approvalStatus === 1) {
                            statusText = "Approved";
                            statusBadge = "bg-success";
                        } else if (h.approvalStatus === 2) {
                            statusText = "Rejected";
                            statusBadge = "bg-danger";
                        }

                        leaveHistoryTbody.innerHTML += `
                            <tr>
                                <td class="fw-semibold text-dark">${h.categoryName}</td>
                                <td>${h.startDate} to ${h.endDate}</td>
                                <td class="fw-bold text-center">${h.duration} Days</td>
                                <td><span class="badge bg-${statusBadge}-subtle text-${statusBadge}">${statusText}</span></td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(err => {
                banner.classList.add("d-none");
                warning.classList.remove("d-none");
                document.getElementById("notRegisteredMsg").textContent = "API connection failure: " + err.message;
            });
    }

    // Check and activate tab from URL hash on load/change
    function handleHashTab() {
        const hash = window.location.hash;
        if (hash) {
            const cleanHash = hash.replace('#', '');
            const tabEl = document.querySelector(`a[id="${cleanHash}-tab"]`);
            if (tabEl) {
                tabEl.click();
            }
        }
    }
    
    handleHashTab();
    window.addEventListener("hashchange", handleHashTab);

    // Helper Utility Formatters
    function isNumeric(n) {
        return !isNaN(parseFloat(n)) && isFinite(n);
    }

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        const num = Number(dateStr);
        const d = !isNaN(num) ? new Date(num) : new Date(dateStr);
        return d.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function formatTime(timestampMs) {
        const d = new Date(parseInt(timestampMs));
        let hrs = d.getHours();
        const mins = String(d.getMinutes()).padStart(2, '0');
        const ampm = hrs >= 12 ? 'PM' : 'AM';
        hrs = hrs % 12;
        hrs = hrs ? hrs : 12;
        return `${String(hrs).padStart(2, '0')}:${mins} ${ampm}`;
    }

    function formatHoursMins(seconds) {
        const secs = parseInt(seconds);
        const hrs = Math.floor(secs / 3600);
        const mins = Math.floor((secs % 3600) / 60);
        if (hrs > 0) {
            return `${hrs}h ${mins}m`;
        }
        return `${mins}m`;
    }

    function formatSecondsToHoursMins(seconds) {
        const hrs = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        return `${hrs}h ${mins}m`;
    }

    // Export Event Listeners
    document.getElementById("btnMyAttendanceExcel").addEventListener("click", function() {
        let csv = [];
        let headers = ["Date", "Shift", "Status", "Entry Time", "Exit Time", "Late By", "Early Exit", "Working Hours", "OT Hours"];
        csv.push(headers.join(","));

        let rows = document.querySelectorAll("#attendanceTableBody tr");
        rows.forEach(row => {
            let cols = row.querySelectorAll("td");
            if (cols.length >= 9) {
                let rowData = [];
                cols.forEach(col => {
                    let text = col.innerHTML
                        .replace(/<br\s*\/?>/gi, ' ')
                        .replace(/<[^>]*>/g, '')
                        .trim()
                        .replace(/"/g, '""');
                    rowData.push(`"${text}"`);
                });
                csv.push(rowData.join(","));
            }
        });

        let csvContent = "data:text/csv;charset=utf-8,\ufeff" + csv.join("\n");
        let encodedUri = encodeURI(csvContent);
        let link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "My_Attendance_Report.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    document.getElementById("btnMyAttendancePdf").addEventListener("click", function() {
        let title = document.getElementById("attendanceHeaderTitle").textContent || "Attendance Report";
        let table = document.querySelector("#attendanceTableBody").closest("table");
        if (!table) return;

        let win = window.open("", "_blank");
        win.document.write(`
            <html>
            <head>
                <title>${title}</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { font-family: 'Inter', sans-serif; padding: 20px; }
                    h2 { color: #198754; font-weight: bold; margin-bottom: 20px; text-transform: uppercase; font-size: 16px; }
                    table { font-size: 11px !important; }
                    th { background-color: #198754 !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    @page { size: landscape; margin: 10mm; }
                </style>
            </head>
            <body>
                <h2>${title}</h2>
                <div class="table-responsive">
                    ${table.outerHTML}
                </div>
                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(function() { window.close(); }, 500);
                    };
                <\/script>
            </body>
            </html>
        `);
        win.document.close();
    });
});
</script>
