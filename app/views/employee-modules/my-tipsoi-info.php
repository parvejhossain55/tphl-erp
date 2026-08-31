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
                        <li class="nav-item">
                            <a class="nav-link text-dark" id="approvals-tab" data-bs-toggle="tab" href="#approvalsTabContent" role="tab" aria-controls="approvalsTabContent" aria-selected="false">
                                <i class="fa-solid fa-user-check text-primary me-2"></i>Approval Pending
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-dark" id="apply-leave-tab" data-bs-toggle="tab" href="#applyLeaveTabContent" role="tab" aria-controls="applyLeaveTabContent" aria-selected="false">
                                <i class="fa-solid fa-calendar-plus text-success me-2"></i>Apply Leave
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-dark" id="applied-leave-list" data-bs-toggle="tab" href="#appliedLeaveListTabContent" role="tab" aria-controls="appliedLeaveListTabContent" aria-selected="false">
                                <i class="fa-solid fa-list-check text-secondary me-2"></i>Applied Leave List
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-dark" id="late-request-tab" data-bs-toggle="tab" href="#lateRequestTabContent" role="tab" aria-controls="lateRequestTabContent" aria-selected="false">
                                <i class="fa-solid fa-clock text-danger me-2"></i>Late Request
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

                <!-- 5. Approval Pending Tab (Requests Pending My Approval) -->
                <div class="tab-pane fade" id="approvalsTabContent" role="tabpanel" aria-labelledby="approvals-tab">
                    <div class="card custom-card border-top border-4 border-primary shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-3 bg-white border-bottom">
                            <h5 class="card-title fw-bold mb-0 text-dark">
                                <i class="fa-solid fa-user-clock text-primary me-2"></i>Requests Pending My Approval
                            </h5>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 fs-12" id="approvalsCountBadge">Total Pending: 0</span>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <!-- Filter Bar -->
                            <div class="row mb-4 align-items-end  p-3 rounded border">
                                <div class="col-md-3 col-12">
                                    <label class="form-label fs-12 fw-bold text-secondary mb-1">Request Type</label>
                                    <select class="form-select form-select-sm" id="approvalReqTypeFilter">
                                        <option value="all">All Types</option>
                                        <option value="Leave Request">Leave Request</option>
                                        <option value="Manual Entry">Manual Entry</option>
                                        <option value="Late Excuse">Late Excuse</option>
                                        <option value="Field Visit">Field Visit</option>
                                    </select>
                                </div>
                                <div class="col-md-3 col-12">
                                    <label class="form-label fs-12 fw-bold text-secondary mb-1">Approval Stage</label>
                                    <select class="form-select form-select-sm" id="approvalStageFilter">
                                        <option value="all">All Pending Stages</option>
                                        <option value="first">Pending 1st Approval</option>
                                        <option value="second">Pending 2nd Approval</option>
                                    </select>
                                </div>
                                <div class="col-md-4 col-12">
                                    <label class="form-label fs-12 fw-bold text-secondary mb-1">Search Employee</label>
                                    <input type="text" id="approvalSearchInput" class="form-control form-control-sm" placeholder="Search by name, ID, department...">
                                </div>
                                <div class="col-md-2 col-12">
                                    <button type="button" id="btnRefreshApprovals" class="btn btn-sm btn-outline-primary w-100 fw-semibold">
                                        <i class="fa-solid fa-rotate me-1"></i> Refresh
                                    </button>
                                </div>
                            </div>

                            <!-- Approvals Table -->
                            <div class="table-responsive">
                                <table class="table table-hover table-striped table-bordered align-middle text-start fs-12 mb-0" id="approvalsTable">
                                    <thead class="table-light text-secondary">
                                        <tr>
                                            <th style="width: 45px;">SN</th>
                                            <th style="width: 85px;">Office ID</th>
                                            <th>Employee Name</th>
                                            <th>Department</th>
                                            <th>Designation</th>
                                            <th style="width: 140px;">Request Type</th>
                                            <th style="width: 120px;">Period</th>
                                            <th>Reason</th>
                                            <th style="width: 180px;">First Approver</th>
                                            <th style="width: 180px;">Second Approver</th>
                                            <th style="width: 95px;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="approvalsTableBody">
                                        <tr>
                                            <td colspan="11" class="text-center py-4 text-muted">
                                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                                Loading pending approvals...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div id="noApprovalsMsg" class="text-muted text-center py-5 d-none">
                                <i class="fa-regular fa-circle-check fs-1 mb-2 d-block text-success animate__animated animate__pulse animate__infinite"></i>
                                <h6 class="fw-bold text-dark mb-1">No Pending Approvals</h6>
                                <p class="text-muted fs-13 mb-0">You have no requests pending for your approval. All caught up!</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. Apply Leave Tab -->
                <div class="tab-pane fade" id="applyLeaveTabContent" role="tabpanel" aria-labelledby="apply-leave-tab">
                    <div class="card custom-card border-top border-4 border-success shadow-sm">
                        <div class="card-header py-3 bg-white border-bottom">
                            <h5 class="card-title fw-bold mb-0 text-dark">
                                <i class="fa-solid fa-calendar-plus text-success me-2"></i>Apply for Leave
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <form id="applyLeaveForm" enctype="multipart/form-data">
                                <div class="row ">
                                    <!-- Leave Category -->
                                    <div class="col-md-6 col-12">
                                        <label class="form-label fw-bold text-secondary mb-1">Leave Category <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="leaveCategoryId" name="leaveCategoryId" required>
                                            <option value="" disabled selected>Select Leave Category...</option>
                                            <!-- Dynamic Categories -->
                                        </select>
                                    </div>
                                    <!-- Half Day -->
                                    <div class="col-md-6 col-12 d-flex align-items-end">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" id="isHalfDay" name="isHalfDay" value="true">
                                            <label class="form-check-label fw-bold text-secondary" for="isHalfDay">Half Day Leave</label>
                                        </div>
                                    </div>
                                    <!-- Start Date -->
                                    <div class="col-md-6 col-12">
                                        <label class="form-label fw-bold text-secondary mb-1">Start Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm" id="leaveStartDate" name="startDate" required>
                                    </div>
                                    <!-- End Date -->
                                    <div class="col-md-6 col-12">
                                        <label class="form-label fw-bold text-secondary mb-1">End Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm" id="leaveEndDate" name="endDate" required>
                                    </div>
                                    <!-- Reason -->
                                    <div class="col-12">
                                        <label class="form-label fw-bold text-secondary mb-1">Reason <span class="text-danger">*</span></label>
                                        <textarea class="form-control form-control-sm" id="leaveReason" name="reason" rows="3" required placeholder="State your reason for leave..."></textarea>
                                    </div>
                                    <!-- Attachment -->
                                    <div class="col-md-6 col-12">
                                        <label class="form-label fw-bold text-secondary mb-1">Attachment <span class="text-muted">(Optional)</span></label>
                                        <input type="file" class="form-control form-control-sm" id="leaveAttachment" name="attachment">
                                    </div>
                                </div>
                                <div class="mt-4 pt-3 border-top d-flex gap-2">
                                    <button type="submit" class="btn btn-sm btn-primary fw-semibold px-4 d-flex align-items-center" id="btnApplyLeave">
                                        <i class="fa-solid fa-paper-plane me-2"></i> Submit Application
                                    </button>
                                    <button type="reset" class="btn btn-sm btn-outline-secondary fw-semibold px-4">Reset</button>
                                </div>
                                <div id="applyLeaveError" class="alert alert-danger mt-3 d-none fs-12 py-2" role="alert"></div>
                                <div id="applyLeaveSuccess" class="alert alert-success mt-3 d-none fs-12 py-2" role="alert"></div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Applied Leave List Tab -->
                <div class="tab-pane fade" id="appliedLeaveListTabContent" role="tabpanel" aria-labelledby="applied-leave-list">
                    <div class="card custom-card border-top border-4 border-secondary shadow-sm">
                        <div class="card-header py-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="card-title fw-bold mb-0 text-dark">
                                <i class="fa-solid fa-list-check text-secondary me-2"></i>Applied Leave List
                            </h5>
                            <button type="button" class="btn btn-sm btn-primary" id="btnRefreshAppliedLeaves">
                                <i class="fa-solid fa-arrow-rotate-right me-1"></i> Refresh Data
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <!-- Filters -->
                            <div class="bg-light p-3 border-bottom d-flex gap-2 align-items-center flex-wrap">
                                <div style="width: 200px;">
                                    <label class="form-label small fw-semibold text-muted mb-1">Search Employee</label>
                                    <input type="text" id="filterKeyword" class="form-control form-control-sm" placeholder="Name or ID...">
                                </div>
                                <div style="width: 150px;">
                                    <label class="form-label small fw-semibold text-muted mb-1">Status</label>
                                    <select id="filterStatus" class="form-select form-select-sm">
                                        <option value="" selected>All Statuses</option>
                                        <option value="0">Pending</option>
                                        <option value="1">Approved</option>
                                        <option value="2">Rejected</option>
                                    </select>
                                </div>
                                <div class="ms-auto mt-4">
                                    <button class="btn btn-sm btn-dark" id="btnApplyFilters">Apply Filters</button>
                                </div>
                            </div>

                            <!-- Table -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="min-width: 1000px;">
                                    <thead class="table-light text-secondary">
                                        <tr>
                                            <th>SN</th>
                                            <th>Emp ID</th>
                                            <th>Employee Name</th>
                                            <th>Category</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Days</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="appliedLeaveTableBody">
                                        <tr>
                                            <td colspan="10" class="text-center py-4 text-muted">
                                                <span class="spinner-border spinner-border-sm me-2" role="status"></span> Loading data...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination Info -->
                            <div class="p-3 border-top bg-light d-flex justify-content-between align-items-center">
                                <span class="text-muted small fw-semibold" id="paginationInfo"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Late Request Tab -->
                <div class="tab-pane fade" id="lateRequestTabContent" role="tabpanel" aria-labelledby="late-request-tab">
                    <div class="card custom-card border-top border-4 border-danger shadow-sm">
                        <div class="card-header py-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="card-title fw-bold mb-0 text-dark">
                                <i class="fa-solid fa-clock text-danger me-2"></i>Late Request
                            </h5>
                            <button type="button" class="btn btn-sm btn-primary" id="btnCreateLateRequest" data-bs-toggle="modal" data-bs-target="#createLateRequestModal">
                                <i class="fa-solid fa-plus me-1"></i> Create Late Request
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <!-- Filters -->
                            <div class="bg-light p-3 border-bottom d-flex gap-2 align-items-center justify-content-between">
                                <div class="d-flex gap-2 align-items-center">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefreshLateRequests">
                                        <i class="fa-solid fa-arrow-rotate-right me-1"></i> Refresh
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Table -->
                            <div class="table-responsive">
                                <table class="table table-hover table-striped align-middle fs-12 mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Employee</th>
                                            <th>Attendance Time</th>
                                            <th>Late By</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Approval Progress</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="lateRequestTableBody">
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                <span class="spinner-border spinner-border-sm me-2" role="status"></span> Loading data...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Create Late Request Modal -->
        <div class="modal fade" id="createLateRequestModal" tabindex="-1" aria-labelledby="createLateRequestModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createLateRequestModalLabel">Create Late Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="createLateRequestForm">
                            <div class="mb-3">
                                <label for="lateRequestDate" class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="lateRequestDate" required>
                            </div>
                            <div class="mb-3">
                                <label for="lateRequestReason" class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="lateRequestReason" rows="3" required placeholder="Reason for being late..."></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="btnSubmitLateRequest">Submit Request</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Late Request Modal -->
        <div class="modal fade" id="actionLateRequestModal" tabindex="-1" aria-labelledby="actionLateRequestModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="actionLateRequestModalLabel">Late Request Action</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="actionLateRequestForm">
                            <input type="hidden" id="actionLateRequestId">
                            <input type="hidden" id="actionLateRequestType">
                            <div class="mb-3">
                                <label for="actionLateRequestComment" class="form-label fw-semibold">Comment</label>
                                <textarea class="form-control" id="actionLateRequestComment" rows="3" placeholder="Optional comment..."></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="btnSubmitActionLateRequest">Submit Action</button>
                    </div>
                </div>
            </div>
        </div>


<script>
const baseUrl = window.base_url || '<?= $GLOBALS['base_url'] ?>';

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
                document.getElementById("pOfficeId").setAttribute("data-tipsoi-id", profile.id || "");
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
                    [...response.dates].reverse().forEach(d => {
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

    // ── Approval Pending Tab Logic ──
    let allTipsoiRequests = [];
    let loggedUserApproverInfo = null;

    function loadApprovalsData() {
        const tbody = document.getElementById("approvalsTableBody");
        if (!tbody) return;

        tbody.innerHTML = `
            <tr>
                <td colspan="11" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    Fetching pending approval requests from Tipsoi API...
                </td>
            </tr>
        `;

        fetch(`${baseUrl}/api/hrm/tipsoi-requests`)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                allTipsoiRequests = res.data || [];
                console.log("Fetched Tipsoi Requests Data:", allTipsoiRequests);
                loggedUserApproverInfo = res.logged_user || null;
                console.log("Logged User Approver Info:", loggedUserApproverInfo);
                renderApprovalsTable();
            } else {
                tbody.innerHTML = `<tr><td colspan="11" class="text-center text-danger py-4">${res.message || 'Failed to load requests'}</td></tr>`;
            }
        })
        .catch(err => {
            tbody.innerHTML = `<tr><td colspan="11" class="text-center text-danger py-4">${err.message || 'Network error'}</td></tr>`;
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
                if (cleanHash === 'approvals') {
                    loadApprovalsData();
                }
            }
        }
    }
    
    handleHashTab();
    window.addEventListener("hashchange", handleHashTab);

    function renderApprovalsTable() {
        const tbody = document.getElementById("approvalsTableBody");
        const noMsg = document.getElementById("noApprovalsMsg");
        const badge = document.getElementById("approvalsCountBadge");
        const typeFilter = document.getElementById("approvalReqTypeFilter") ? document.getElementById("approvalReqTypeFilter").value : 'all';
        const stageFilter = document.getElementById("approvalStageFilter") ? document.getElementById("approvalStageFilter").value : 'all';
        const searchVal = document.getElementById("approvalSearchInput") ? document.getElementById("approvalSearchInput").value.toLowerCase().trim() : '';

        if (!tbody) return;

        const loggedUser = loggedUserApproverInfo;
        const userEmail = loggedUser ? (loggedUser.email || '').toLowerCase().trim() : '';
        const userOfficeId = loggedUser ? (loggedUser.employee_id || loggedUser.tipsoi_office_id || '').trim() : '';
        const userName = loggedUser ? (loggedUser.full_name || '').toLowerCase().trim() : '';
        const userTipsoiId = loggedUser ? String(loggedUser.tipsoi_id || '') : '';
        const isHr = loggedUser ? loggedUser.is_hr : false;

        const normalizeName = (s) => String(s || '').toLowerCase().replace(/[^a-z0-9]/g, '');
        const normUser = normalizeName(userName);

        const matchedRecords = allTipsoiRequests.filter(rec => {
            if (typeFilter !== 'all') {
                if (rec.requestType !== typeFilter && rec.subType !== typeFilter) return false;
            }

            if (searchVal) {
                const name = String(rec.employeeName || '').toLowerCase();
                const oid = String(rec.employeeOfficeId || '').toLowerCase();
                const dept = String(rec.departmentName || '').toLowerCase();
                const desig = String(rec.designationName || '').toLowerCase();
                if (!name.includes(searchVal) && !oid.includes(searchVal) && !dept.includes(searchVal) && !desig.includes(searchVal)) {
                    return false;
                }
            }

            const firstAppName = String(rec.firstApproverName || '').trim();
            const normFirst = normalizeName(firstAppName);
            const firstAppId = String(rec.firstApproverId || '');
            const firstStatus = rec.firstApprovalStatus;

            const secondAppName = String(rec.secondApproverName || '').trim();
            const normSecond = normalizeName(secondAppName);
            const secondAppId = String(rec.secondApproverId || '');
            const secondStatus = rec.secondApprovalStatus;

            const isFirstApprover = (userTipsoiId && firstAppId && userTipsoiId === firstAppId) ||
                (normUser && normFirst && (normUser.includes(normFirst) || normFirst.includes(normUser))) ||
                (userOfficeId && firstAppName && firstAppName.includes(userOfficeId));

            const isSecondApprover = (userTipsoiId && secondAppId && userTipsoiId === secondAppId) ||
                (normUser && normSecond && (normUser.includes(normSecond) || normSecond.includes(normUser))) ||
                (userOfficeId && secondAppName && secondAppName.includes(userOfficeId));

            const isMyOwnRequest = (userOfficeId && String(rec.employeeOfficeId || '') === userOfficeId) || 
                                   (normUser && normUser === normalizeName(rec.employeeName));

            let isForMe = false;
            
            // If the user sent the request themselves
            if (isMyOwnRequest) {
                isForMe = true;
            }
            
            // If the user is the first approver (and it's pending)
            if (isFirstApprover && firstStatus == 0) {
                isForMe = true;
            }
            
            // If the user is the second approver (and it's pending)
            if (isSecondApprover && secondStatus == 0) {
                isForMe = true;
            }

            // Apply dropdown stage filter if selected
            if (isForMe && stageFilter === 'first' && firstStatus != 0) {
                isForMe = false;
            } else if (isForMe && stageFilter === 'second' && secondStatus != 0) {
                isForMe = false;
            }

            return isForMe;
        });

        if (badge) badge.textContent = `Total Pending: ${matchedRecords.length}`;

        if (matchedRecords.length === 0) {
            tbody.innerHTML = '';
            if (noMsg) noMsg.classList.remove("d-none");
            return;
        }

        if (noMsg) noMsg.classList.add("d-none");

        const getApproverBadge = (status, name) => {
            if (!name) return '<span class="text-muted">—</span>';
            if (status === 1) return `<div class="text-success fw-semibold"><i class="fa-solid fa-circle-check me-1"></i>${name} <small class="text-success">(Approved)</small></div>`;
            if (status === 2) return `<div class="text-danger fw-semibold"><i class="fa-solid fa-circle-xmark me-1"></i>${name} <small class="text-danger">(Rejected)</small></div>`;
            return `<div class="text-warning fw-semibold"><i class="fa-solid fa-hourglass-half me-1"></i>${name} <small class="text-warning">(Pending)</small></div>`;
        };

        let sn = 1;
        tbody.innerHTML = matchedRecords.map(rec => {
            const datePeriod = (rec.startDate === rec.endDate) ? rec.startDate : `${rec.startDate} to ${rec.endDate}`;
            const firstAppBadge = getApproverBadge(rec.firstApprovalStatus, rec.firstApproverName);
            const secondAppBadge = getApproverBadge(rec.secondApprovalStatus, rec.secondApproverName);

            return `
                <tr>
                    <td class="text-muted fw-semibold text-center">${sn++}</td>
                    <td class="fw-semibold text-muted">${rec.employeeOfficeId || '—'}</td>
                    <td class="fw-bold text-dark">${rec.employeeName}</td>
                    <td>${rec.departmentName}</td>
                    <td><span class="badge bg-light text-dark border">${rec.designationName}</span></td>
                    <td><span class="badge bg-primary-subtle text-primary fw-semibold">${rec.subType || rec.requestType}</span></td>
                    <td class="text-secondary fw-semibold">${datePeriod}</td>
                    <td class="text-muted" style="max-width: 250px; word-wrap: break-word;">${rec.reason || '—'}</td>
                    <td>${firstAppBadge}</td>
                    <td>${secondAppBadge}</td>
                    <td><span class="badge bg-warning text-dark px-2 py-1"><i class="fa-solid fa-hourglass-half me-1"></i>Pending</span></td>
                </tr>
            `;
        }).join('');
    }

    document.getElementById("approvals-tab")?.addEventListener("click", function() {
        loadApprovalsData();
    });
    document.getElementById("btnRefreshApprovals")?.addEventListener("click", function() {
        loadApprovalsData();
    });
    document.getElementById("approvalReqTypeFilter")?.addEventListener("change", renderApprovalsTable);
    document.getElementById("approvalStageFilter")?.addEventListener("change", renderApprovalsTable);
    document.getElementById("approvalSearchInput")?.addEventListener("input", renderApprovalsTable);

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
    
    // ==========================================
    // 6. Apply Leave Functionality
    // ==========================================
    const applyLeaveForm = document.getElementById("applyLeaveForm");
    const leaveCategoryId = document.getElementById("leaveCategoryId");
    
    // Fetch Leave Categories
    // Fetch Leave Categories immediately
    function loadLeaveCategories() {
        fetch(`${baseUrl}/api/hrm/leave-category`)
            .then(async res => {
                if (!res.ok) {
                    const text = await res.text();
                    throw new Error("HTTP " + res.status + ": " + text);
                }
                return res.json();
            })
            .then(data => {
                console.log("Leave Categories Data:", data);
                // Also check if data is in a different format
                const list = data.leaveCategoryList || data.data || data;
                if (Array.isArray(list)) {
                    let html = '<option value="" disabled selected>Select Leave Category...</option>';
                    list.forEach(cat => {
                        // Accept status 1, or string "1", or no status field at all
                        if (cat.status === undefined || cat.status == 1 || cat.status === true) { 
                            html += `<option value="${cat.id}">${cat.name}</option>`;
                        }
                    });
                    if (leaveCategoryId) leaveCategoryId.innerHTML = html;
                } else {
                    console.error("Leave categories list not found in response:", data);
                }
            })
            .catch(err => {
                console.error("Failed to load leave categories", err);
                if (leaveCategoryId) {
                    leaveCategoryId.innerHTML = '<option value="" disabled selected>Error loading categories</option>';
                }
            });
    }
    
    // Load immediately to avoid click event issues
    loadLeaveCategories();

    if (applyLeaveForm) {
        applyLeaveForm.addEventListener("submit", function(e) {
            e.preventDefault();
            
            const btnSubmit = document.getElementById("btnApplyLeave");
            const errorAlert = document.getElementById("applyLeaveError");
            const successAlert = document.getElementById("applyLeaveSuccess");
            
            errorAlert.classList.add("d-none");
            successAlert.classList.add("d-none");
            
            const startDateEl = document.getElementById("leaveStartDate");
            const endDateEl = document.getElementById("leaveEndDate");
            
            const startDateVal = startDateEl.value;
            const endDateVal = endDateEl.value;
            
            if (new Date(endDateVal) < new Date(startDateVal)) {
                errorAlert.textContent = "End date cannot be earlier than start date.";
                errorAlert.classList.remove("d-none");
                return;
            }
            
            // Get Tipsoi Employee ID from the already fetched profile
            const tipsoiEmpId = document.getElementById("pOfficeId") ? document.getElementById("pOfficeId").getAttribute("data-tipsoi-id") : null;
            
            if (!tipsoiEmpId) {
                errorAlert.textContent = "Could not find your Tipsoi Profile ID. Please make sure you are registered.";
                errorAlert.classList.remove("d-none");
                return;
            }

            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Submitting...';

            const startYear = new Date(startDateVal).getFullYear();
            const fiscalYearStartDate = new Date(startYear, 0, 1, 0, 0, 0).getTime(); // Jan 1st 00:00:00
            const fiscalYearEndDate = new Date(startYear, 11, 31, 23, 59, 59).getTime(); // Dec 31st 23:59:59

            const startDateMs = new Date(startDateVal + 'T00:00:00').getTime();
            const endDateMs = new Date(endDateVal + 'T23:59:59').getTime();

            const postBodyData = {
                leaveCategoryId: parseInt(document.getElementById("leaveCategoryId").value),
                reason: document.getElementById("leaveReason").value,
                forceApproval: false,
                fiscalYearStartDate: fiscalYearStartDate,
                fiscalYearEndDate: fiscalYearEndDate,
                startDate: startDateMs,
                endDate: endDateMs,
                isHalfDay: document.getElementById("isHalfDay").checked
            };

            const formData = new FormData();
            formData.append('postBody', JSON.stringify(postBodyData));
            
            const fileInput = document.getElementById("leaveAttachment");
            if (fileInput.files.length > 0) {
                formData.append('file', fileInput.files[0]); // sending as 'file' to match backend expectation if we just pass through
            }

            fetch(`${baseUrl}/api/hrm/leave-management/apply/employee/${tipsoiEmpId}`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success || data.message === "Success" || (data.code && data.code === 200)) {
                    successAlert.textContent = "Leave application submitted successfully!";
                    successAlert.classList.remove("d-none");
                    applyLeaveForm.reset();
                    // Optionally refresh dashboard after a short delay
                    setTimeout(() => {
                        document.getElementById("btnRefreshApprovals")?.click();
                    }, 2000);
                } else {
                    errorAlert.textContent = data.message || "Failed to submit leave application.";
                    errorAlert.classList.remove("d-none");
                }
            })
            .catch(err => {
                console.error("Error submitting leave:", err);
                errorAlert.textContent = "An error occurred while submitting. Please try again.";
                errorAlert.classList.remove("d-none");
            })
            .finally(() => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="fa-solid fa-paper-plane me-2"></i> Submit Application';
            });
        });
    }
    // ==========================================
    // 7. Applied Leave List Functionality
    // ==========================================
    const appliedLeaveTableBody = document.getElementById("appliedLeaveTableBody");
    const btnRefreshAppliedLeaves = document.getElementById("btnRefreshAppliedLeaves");
    const btnApplyFilters = document.getElementById("btnApplyFilters");
    const filterStatus = document.getElementById("filterStatus");
    const filterKeyword = document.getElementById("filterKeyword");
    const paginationInfo = document.getElementById("paginationInfo");

    function fetchAppliedLeaves() {
        if (!appliedLeaveTableBody) return;
        
        appliedLeaveTableBody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-4 text-muted">
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span> Loading data...
                </td>
            </tr>
        `;
        
        const params = new URLSearchParams();
        params.append('page', '0');
        params.append('count', '2000'); // Increased count to get enough records for client-side filtering
        
        if (filterStatus.value !== "") {
            params.append('status', filterStatus.value);
        } else {
            params.append('status', '0');
            params.append('status', '1');
            params.append('status', '2');
            params.append('status', '3');
            params.append('status', '4');
            params.append('status', '5');
        }

        if (filterKeyword.value.trim() !== "") {
            params.append('keyword', filterKeyword.value.trim());
        }

        fetch(`${baseUrl}/api/hrm/applied-leave-list?${params.toString()}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    let finalData = res.data || [];

                    console.log("Fetched Applied Leaves Data:", finalData);
                    


                    renderAppliedLeavesTable(finalData);
                } else {
                    appliedLeaveTableBody.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-4">${res.message || 'Failed to load'}</td></tr>`;
                }
            })
            .catch(err => {
                console.error(err);
                appliedLeaveTableBody.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-4">Network error occurred</td></tr>`;
            });
    }

    function renderAppliedLeavesTable(data) {
        console.log("Rendering Applied Leaves Table with data:", data);
        if (data.length === 0) {
            appliedLeaveTableBody.innerHTML = `<tr><td colspan="10" class="text-center text-muted py-4">No leave history found.</td></tr>`;
            if(paginationInfo) paginationInfo.textContent = "Showing 0 records";
            return;
        }

        const userOfficeIdEl = document.getElementById("pOfficeId");
        const userTipsoiId = userOfficeIdEl ? userOfficeIdEl.getAttribute("data-tipsoi-id") : '';

        let html = '';
        let sn = 1;
        data.forEach(item => {
            const startDate = item.startDate ? new Date(item.startDate).toLocaleDateString() : 'N/A';
            const endDate = item.endDate ? new Date(item.endDate).toLocaleDateString() : 'N/A';
            
            let statusBadge = '';
            const statusInt = parseInt(item.approvalStatus || item.status);
            if (statusInt === 1) statusBadge = '<span class="badge bg-success">Approved</span>';
            else if (statusInt === 2) statusBadge = '<span class="badge bg-danger">Rejected</span>';
            else statusBadge = '<span class="badge bg-warning text-dark">Pending</span>';

            let actionBtn = '-';
            const firstAppId = String(item.firstApproverId || '');
            const secondAppId = String(item.secondApproverId || '');
            const firstStatus = parseInt(item.firstApprovalStatus || 0);
            const secondStatus = parseInt(item.secondApprovalStatus || 0);
            
            if (userTipsoiId && statusInt !== 1 && statusInt !== 2) {
                if (firstAppId === userTipsoiId && firstStatus === 0) {
                    actionBtn = `<button class="btn btn-sm btn-success btn-approve-leave" data-id="${item.id}">Approve</button>`;
                } else if (secondAppId === userTipsoiId && secondStatus === 0 && firstStatus === 1) {
                    actionBtn = `<button class="btn btn-sm btn-success btn-approve-leave" data-id="${item.id}">Approve</button>`;
                }
            }

            html += `
                <tr>
                    <td class="text-muted">${sn++}</td>
                    <td class="fw-semibold">${item.employeeOfficeId || item.employeeId || 'N/A'}</td>
                    <td class="fw-bold">${item.employeeName || 'Unknown'}</td>
                    <td><span class="badge bg-light text-primary border border-primary-subtle">${item.categoryName || 'Leave'}</span></td>
                    <td>${startDate}</td>
                    <td>${endDate}</td>
                    <td class="fw-semibold">${item.days || item.duration || '-'}</td>
                    <td class="text-muted" style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${item.reason || ''}">${item.reason || ''}</td>
                    <td>${statusBadge}</td>
                    <td>${actionBtn}</td>
                </tr>
            `;
        });
        
        appliedLeaveTableBody.innerHTML = html;
        if(paginationInfo) paginationInfo.textContent = `Showing ${data.length} records`;

        // Attach click listeners to approve buttons
        document.querySelectorAll('.btn-approve-leave').forEach(btn => {
            btn.addEventListener('click', function() {
                const leaveId = this.getAttribute('data-id');
                approveLeaveRequest(leaveId, this);
            });
        });
    }

    function approveLeaveRequest(leaveId, btnEl) {
        if (!confirm("Are you sure you want to approve this leave request?")) return;
        
        const originalText = btnEl.innerHTML;
        btnEl.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
        btnEl.disabled = true;

        fetch(`${baseUrl}/api/hrm/leave-management/approve/${leaveId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                alert("Leave request approved successfully!");
                fetchAppliedLeaves(); // Refresh the table
            } else {
                alert("Failed to approve leave: " + (res.message || "Unknown error"));
                btnEl.innerHTML = originalText;
                btnEl.disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            alert("Network error occurred.");
            btnEl.innerHTML = originalText;
            btnEl.disabled = false;
        });
    }

    if (btnRefreshAppliedLeaves) btnRefreshAppliedLeaves.addEventListener("click", fetchAppliedLeaves);
    if (btnApplyFilters) btnApplyFilters.addEventListener("click", fetchAppliedLeaves);

    // Initial load when tab is clicked for the first time
    const appliedLeaveListTab = document.getElementById("applied-leave-list");
    if (appliedLeaveListTab) {
        let appliedLeaveLoaded = false;
        appliedLeaveListTab.addEventListener("shown.bs.tab", function () {
            if (!appliedLeaveLoaded) {
                fetchAppliedLeaves();
                appliedLeaveLoaded = true;
            }
        });
    }

    // Also check on initial load in case hash is already #applied-leave-list
    if (window.location.hash === '#applied-leave-list') {
        fetchAppliedLeaves();
    }

    // -------------------------------------------------------------------------
    // LATE REQUEST LOGIC
    // -------------------------------------------------------------------------
    
    const lateRequestTableBody = document.getElementById("lateRequestTableBody");
    const btnRefreshLateRequests = document.getElementById("btnRefreshLateRequests");
    const btnSubmitLateRequest = document.getElementById("btnSubmitLateRequest");
    const btnSubmitActionLateRequest = document.getElementById("btnSubmitActionLateRequest");

    function fetchLateRequests() {
        if (!lateRequestTableBody) return;
        
        const tipsoiEmpId = document.getElementById("pOfficeId") ? document.getElementById("pOfficeId").getAttribute("data-tipsoi-id") : null;
        if (!tipsoiEmpId) {
            lateRequestTableBody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">Employee ID not found.</td></tr>`;
            return;
        }

        lateRequestTableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span> Fetching data...
                </td>
            </tr>
        `;
        
        // Use the history endpoint as it provides comprehensive state
        fetch(`${baseUrl}/api/hrm/late-approval-management/late-approvals/${tipsoiEmpId}`)
            .then(res => {
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }
                return res.text();
            })
            .then(text => {
                let res;
                try {
                    res = JSON.parse(text);
                } catch (e) {
                    console.error("Failed to parse JSON: ", text);
                    throw new Error("Invalid JSON response from server.");
                }

                if (res.success && res.lateLogList) {
                    const data = res.lateLogList;
                    if (!Array.isArray(data) || data.length === 0) {
                        lateRequestTableBody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">No late requests found.</td></tr>`;
                        return;
                    }

                    let html = '';
                    data.forEach(item => {
                        let attendanceDate = item.attendanceTime ? new Date(item.attendanceTime).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
                        let attendanceTime = item.attendanceTime ? new Date(item.attendanceTime).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '-';

                        // Status Badge
                        let statusBadge = '';
                        if (item.status === 0) statusBadge = '<span class="badge bg-warning text-dark">Pending</span>';
                        else if (item.status === 1) statusBadge = '<span class="badge bg-success">Approved</span>';
                        else if (item.status === 2) statusBadge = '<span class="badge bg-danger">Rejected</span>';
                        else if (item.status === 3) statusBadge = '<span class="badge bg-secondary">Cancelled</span>';
                        else statusBadge = `<span class="badge bg-secondary">Unknown (${item.status})</span>`;

                        // Approval Progress
                        let approvalProgress = `
                            <div class="fs-11 text-muted text-start">
                                <div>1st: ${getApprovalStatusText(item.firstApprovalStatus)}</div>
                                <div>2nd: ${getApprovalStatusText(item.secondApprovalStatus)}</div>
                            </div>
                        `;

                        // Actions
                        let actionsHtml = '';
                        if (item.status === 0) {
                            actionsHtml = `
                                <button class="btn btn-sm btn-outline-success action-late-btn" data-id="${item.id}" data-type="approve" title="Approve"><i class="fa-solid fa-check"></i></button>
                                <button class="btn btn-sm btn-outline-danger action-late-btn" data-id="${item.id}" data-type="reject" title="Reject"><i class="fa-solid fa-xmark"></i></button>
                                <button class="btn btn-sm btn-outline-secondary action-late-btn" data-id="${item.id}" data-type="cancel" title="Cancel"><i class="fa-solid fa-ban"></i></button>
                            `;
                        }

                        html += `
                            <tr>
                                <td>${attendanceDate}</td>
                                <td>${item.employeeName || '-'}</td>
                                <td>${attendanceTime}</td>
                                <td>${item.lateByStr || '-'}</td>
                                <td>${item.reason || '-'}</td>
                                <td>${statusBadge}</td>
                                <td>${approvalProgress}</td>
                                <td>${actionsHtml}</td>
                            </tr>
                        `;
                    });
                    
                    lateRequestTableBody.innerHTML = html;
                    
                    // Add event listeners to dynamic action buttons
                    document.querySelectorAll('.action-late-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const id = this.getAttribute('data-id');
                            const type = this.getAttribute('data-type');
                            
                            document.getElementById('actionLateRequestId').value = id;
                            document.getElementById('actionLateRequestType').value = type;
                            
                            const modalLabel = document.getElementById('actionLateRequestModalLabel');
                            if (type === 'approve') modalLabel.textContent = 'Approve Late Request';
                            if (type === 'reject') modalLabel.textContent = 'Reject Late Request';
                            if (type === 'cancel') modalLabel.textContent = 'Cancel Late Request';
                            
                            const myModal = new bootstrap.Modal(document.getElementById('actionLateRequestModal'));
                            myModal.show();
                        });
                    });

                } else {
                    lateRequestTableBody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">Failed to load: ${res.message || 'Unknown error'}</td></tr>`;
                }
            })
            .catch(err => {
                console.error("Fetch Late Requests Error:", err);
                lateRequestTableBody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">Error: ${err.message || 'Network error occurred.'}</td></tr>`;
            });
    }

    function getApprovalStatusText(status) {
        if (status === 0) return '<span class="text-warning"><i class="fa-solid fa-hourglass-half me-1"></i>Pending</span>';
        if (status === 1) return '<span class="text-success"><i class="fa-solid fa-check me-1"></i>Approved</span>';
        if (status === 2) return '<span class="text-danger"><i class="fa-solid fa-xmark me-1"></i>Rejected</span>';
        return '<span class="text-muted">N/A</span>';
    }

    if (btnRefreshLateRequests) btnRefreshLateRequests.addEventListener("click", fetchLateRequests);

    // Create Late Request
    if (btnSubmitLateRequest) {
        btnSubmitLateRequest.addEventListener("click", function() {
            const tipsoiEmpId = document.getElementById("pOfficeId") ? document.getElementById("pOfficeId").getAttribute("data-tipsoi-id") : null;
            if (!tipsoiEmpId) {
                alert("Employee ID not found.");
                return;
            }

            const dateInput = document.getElementById("lateRequestDate");
            const reasonInput = document.getElementById("lateRequestReason");
            
            if (!dateInput.value || !reasonInput.value) {
                alert("Please fill in all required fields.");
                return;
            }

            const originalText = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            this.disabled = true;

            const postData = {
                attendanceDate: new Date(dateInput.value).getTime(),
                reason: reasonInput.value
            };

            fetch(`${baseUrl}/api/hrm/late-approval-management/apply/employee/${tipsoiEmpId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(postData)
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Late request submitted successfully.'
                        });
                    } else {
                        alert('Late request submitted successfully.');
                    }
                    const modalEl = document.getElementById('createLateRequestModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    
                    dateInput.value = '';
                    reasonInput.value = '';
                    
                    fetchLateRequests();
                } else {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message || 'Failed to submit request.'
                        });
                    } else {
                        alert(res.message || 'Failed to submit request.');
                    }
                }
            })
            .catch(err => {
                console.error(err);
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Network error occurred.'
                    });
                } else {
                    alert('Network error occurred.');
                }
            })
            .finally(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            });
        });
    }

    // Submit Action (Approve/Reject/Cancel)
    if (btnSubmitActionLateRequest) {
        btnSubmitActionLateRequest.addEventListener("click", function() {
            const logId = document.getElementById("actionLateRequestId").value;
            const type = document.getElementById("actionLateRequestType").value;
            const comment = document.getElementById("actionLateRequestComment").value;
            
            const originalText = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            this.disabled = true;

            const postData = {
                approverComment: comment
            };

            fetch(`${baseUrl}/api/hrm/late-approval-management/${type}/${logId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(postData)
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: `Late request ${type}d successfully.`
                        });
                    } else {
                        alert(`Late request ${type}d successfully.`);
                    }
                    const modalEl = document.getElementById('actionLateRequestModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    
                    fetchLateRequests();
                } else {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message || `Failed to ${type} request.`
                        });
                    } else {
                        alert(res.message || `Failed to ${type} request.`);
                    }
                }
            })
            .catch(err => {
                console.error(err);
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Network error occurred.'
                    });
                } else {
                    alert('Network error occurred.');
                }
            })
            .finally(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            });
        });
    }

    // Initial load when tab is clicked for the first time
    const lateRequestTab = document.getElementById("late-request-tab");
    if (lateRequestTab) {
        lateRequestTab.addEventListener("shown.bs.tab", function () {
            fetchLateRequests();
        });
    }

    if (window.location.hash === '#late-request') {
        // Find the tab element and click it
        if (lateRequestTab) {
            new bootstrap.Tab(lateRequestTab).show();
        }
    }
});
</script>
