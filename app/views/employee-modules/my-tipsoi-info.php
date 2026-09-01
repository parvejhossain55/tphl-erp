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
                            <a class="nav-link text-dark" id="late-request-tab" data-bs-toggle="tab" href="#lateRequestTabContent" role="tab" aria-controls="lateRequestTabContent" aria-selected="false">
                                <i class="fa-solid fa-clock text-danger me-2"></i>Late Request
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-dark" id="history-tab" data-bs-toggle="tab" href="#historyTabContent" role="tab" aria-controls="historyTabContent" aria-selected="false">
                                <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>History
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-dark" id="manual-entry-tab" data-bs-toggle="tab" href="#manualEntryTabContent" role="tab" aria-controls="manualEntryTabContent" aria-selected="false">
                                <i class="fa-solid fa-file-pen text-warning me-2"></i>Manual Entry
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
                                <table class="table table-hover table-striped table-bordered text-start fs-12 mb-0 align-middle">
                                    <thead style="background-color: #f8fafc; color: #475569;">
                                        <tr>
                                            <th>Request Type</th>
                                            <th>Requested Period / Date</th>
                                            <th>Reason</th>
                                            <th>Approval Status</th>
                                            <th style="width: 110px;" class="text-center">Action</th>
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
                    <div class="row justify-content-center">
                        <div class="col-lg-8 col-md-10">
                            <div class="card custom-card border-top border-4 border-success shadow-sm">
                                <div class="card-header py-3 bg-white border-bottom">
                                    <h5 class="card-title fw-bold mb-1 text-dark">
                                        <i class="fa-solid fa-calendar-plus text-success me-2"></i>Apply for Leave
                                    </h5>
                                    <small class="text-muted">Submit a formal leave application to your line managers and HR</small>
                                </div>
                                <div class="card-body p-4">
                                    <!-- Approvers Preview Banner -->
                                    <div class="row g-2 mb-4 p-3 bg-light rounded-3 border" id="leaveApproversRow">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                                    <i class="fa-solid fa-user-check fs-13"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block fs-11 fw-semibold text-uppercase">1st Approver</small>
                                                    <span class="fw-bold text-dark fs-12" id="formFirstApproverName">Loading...</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar avatar-sm bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                                    <i class="fa-solid fa-user-shield fs-13"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block fs-11 fw-semibold text-uppercase">2nd Approver</small>
                                                    <span class="fw-bold text-dark fs-12" id="formSecondApproverName">Loading...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <form id="applyLeaveForm" enctype="multipart/form-data">
                                        <div class="row g-3">
                                            <!-- 1. Leave Category -->
                                            <div class="col-12">
                                                <label class="form-label fw-bold text-dark mb-1">
                                                    Leave Category <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select fw-semibold" id="leaveCategoryId" name="leaveCategoryId" required>
                                                    <option value="" disabled selected>Select Leave Category...</option>
                                                    <!-- Dynamic Categories with Remaining Balances -->
                                                </select>
                                            </div>

                                            <!-- 2. Leave Duration Mode (Segmented Button Group) -->
                                            <div class="col-12">
                                                <label class="form-label fw-bold text-dark mb-1">Leave Duration Mode <span class="text-danger">*</span></label>
                                                <div class="btn-group w-100" role="group" aria-label="Leave Mode">
                                                    <input type="radio" class="btn-check" name="leaveModeRadio" id="leaveModeFull" value="full" checked autocomplete="off">
                                                    <label class="btn btn-outline-success fw-semibold py-2" for="leaveModeFull">
                                                        <i class="fa-solid fa-sun me-1"></i> Full Day Leave
                                                    </label>

                                                    <input type="radio" class="btn-check" name="leaveModeRadio" id="leaveModeHalf" value="half" autocomplete="off">
                                                    <label class="btn btn-outline-success fw-semibold py-2" for="leaveModeHalf">
                                                        <i class="fa-solid fa-cloud-sun me-1"></i> Half Day (0.5 Day)
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- 3A. Full Day Dates (Start & End Date) -->
                                            <div class="col-12" id="blockFullDayDates">
                                                <div class="row g-3">
                                                    <div class="col-md-6 col-12">
                                                        <label class="form-label fw-bold text-dark mb-1">Start Date <span class="text-danger">*</span></label>
                                                        <input type="date" class="form-control" id="leaveStartDate" name="startDate" value="<?= date('Y-m-d') ?>" required>
                                                    </div>
                                                    <div class="col-md-6 col-12">
                                                        <label class="form-label fw-bold text-dark mb-1">End Date <span class="text-danger">*</span></label>
                                                        <input type="date" class="form-control" id="leaveEndDate" name="endDate" value="<?= date('Y-m-d') ?>" required>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- 3B. Half Day Date (Single Date) -->
                                            <div class="col-12 d-none" id="blockHalfDayDate">
                                                <label class="form-label fw-bold text-dark mb-1">Leave Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="leaveSingleDate" value="<?= date('Y-m-d') ?>">
                                                <div class="form-text text-muted">Half day leave applies for 0.5 day on the selected date.</div>
                                            </div>

                                            <!-- 4. Summary & Balance Info Card -->
                                            <div class="col-12">
                                                <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                    <div class="d-flex align-items-center gap-2 text-primary fw-bold fs-13">
                                                        <i class="fa-solid fa-calculator fs-15"></i>
                                                        <span>Calculated Duration: <strong id="leaveDurationDisplay" class="text-dark">1 Day</strong></span>
                                                    </div>
                                                    <div id="leaveCategoryBalanceBadge" class="d-none">
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-12" id="leaveBalanceText"></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- 5. Reason -->
                                            <div class="col-12">
                                                <label class="form-label fw-bold text-dark mb-1">Reason / Purpose <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="leaveReason" name="reason" rows="3" required placeholder="State your reason for leave..."></textarea>
                                            </div>

                                            <!-- 6. Attachment -->
                                            <div class="col-12">
                                                <label class="form-label fw-semibold text-muted mb-1">Supporting Document / Prescription <span class="text-muted fs-11">(Optional)</span></label>
                                                <input type="file" class="form-control" id="leaveAttachment" name="attachment">
                                            </div>
                                        </div>

                                        <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                                            <button type="reset" class="btn btn-outline-secondary fw-semibold px-4" id="btnResetApplyLeave">Reset</button>
                                            <button type="submit" class="btn btn-primary fw-semibold px-4 d-flex align-items-center" id="btnApplyLeave">
                                                <i class="fa-solid fa-paper-plane me-2"></i> Submit Application
                                            </button>
                                        </div>
                                        <div id="applyLeaveError" class="alert alert-danger mt-3 d-none fs-12 py-2" role="alert"></div>
                                        <div id="applyLeaveSuccess" class="alert alert-success mt-3 d-none fs-12 py-2" role="alert"></div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 7. Late Request Tab -->
                <div class="tab-pane fade" id="lateRequestTabContent" role="tabpanel" aria-labelledby="late-request-tab">
                    <div class="row justify-content-center">
                        <div class="col-lg-8 col-md-10">
                            <div class="card custom-card border-top border-4 border-danger shadow-sm">
                                <div class="card-header py-3 bg-white border-bottom">
                                    <h5 class="card-title fw-bold mb-1 text-dark">
                                        <i class="fa-solid fa-clock text-danger me-2"></i>Apply for Late Arrival Excuse
                                    </h5>
                                    <small class="text-muted">Submit an explanation for biometric attendance lateness to your approvers</small>
                                </div>
                                <div class="card-body p-4">
                                    <!-- Approvers Preview Banner -->
                                    <div class="row g-2 mb-4 p-3 bg-light rounded-3 border" id="lateApproversRow">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar avatar-sm bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                                    <i class="fa-solid fa-user-check fs-13"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block fs-11 fw-semibold text-uppercase">1st Late Approver</small>
                                                    <span class="fw-bold text-dark fs-12" id="formFirstLateApproverName">Loading...</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar avatar-sm bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                                    <i class="fa-solid fa-user-shield fs-13"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block fs-11 fw-semibold text-uppercase">2nd Late Approver</small>
                                                    <span class="fw-bold text-dark fs-12" id="formSecondLateApproverName">Loading...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <form id="formLateArrivalApp">
                                        <div class="row g-3">
                                            <!-- 1. Late Date Selection (Smart Dropdown of Actual Late Entries) -->
                                            <div class="col-12">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <label class="form-label fw-bold text-dark mb-0">
                                                        Select Late Date <span class="text-danger">*</span>
                                                    </label>
                                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fs-12 text-secondary" id="btnToggleCustomDate">
                                                        <i class="fa-solid fa-calendar-day me-1"></i>Switch to Custom Date Picker
                                                    </button>
                                                </div>

                                                <!-- Smart Dropdown of Late Entries -->
                                                <div id="containerSmartLateSelect">
                                                    <select class="form-select fw-semibold" id="lateEntrySelect" required>
                                                        <option value="" disabled selected>Loading actual late records...</option>
                                                    </select>
                                                    <div class="form-text text-muted" id="lateEntriesHelp">
                                                        Automatically loaded from your biometric punch records (last 90 days).
                                                    </div>
                                                </div>

                                                <!-- Manual Custom Date Picker (Hidden by default) -->
                                                <div id="containerCustomLateDate" class="d-none mt-2">
                                                    <input type="date" class="form-control" id="customLateDate" value="<?= date('Y-m-d') ?>">
                                                    <div class="form-text text-muted">Select specific date if not listed above.</div>
                                                </div>
                                            </div>

                                            <!-- 2. Late Details Info Card -->
                                            <div class="col-12" id="lateDetailsInfoCard">
                                                <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div>
                                                            <small class="text-muted d-block fs-11">Shift Start</small>
                                                            <span class="fw-bold text-dark fs-12" id="lateShiftStartTime">—</span>
                                                        </div>
                                                        <div class="border-start ps-3">
                                                            <small class="text-muted d-block fs-11">Actual Punch</small>
                                                            <span class="fw-bold text-danger fs-12" id="lateActualPunchTime">—</span>
                                                        </div>
                                                    </div>
                                                    <div id="lateDurationBadge">
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fs-12" id="lateDurationText">
                                                            <i class="fa-solid fa-clock me-1"></i>Late Duration: —
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- 3. Reason for Late Arrival -->
                                            <div class="col-12">
                                                <label class="form-label fw-bold text-dark mb-1">
                                                    Reason for Late Arrival <span class="text-danger">*</span>
                                                </label>
                                                <textarea class="form-control" id="lateReasonText" rows="3" required placeholder="Explain why you were late (e.g., severe traffic delay, urgent medical appointment, vehicle breakdown)..."></textarea>
                                            </div>
                                        </div>

                                        <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                                            <button type="reset" class="btn btn-outline-secondary fw-semibold px-4" id="btnResetLateApp">Reset</button>
                                            <button type="submit" class="btn btn-danger fw-semibold px-4 d-flex align-items-center" id="btnSubmitLateApp">
                                                <i class="fa-solid fa-paper-plane me-2"></i> Submit Late Request
                                            </button>
                                        </div>
                                        <div id="lateAppAlertError" class="alert alert-danger mt-3 d-none fs-12 py-2" role="alert"></div>
                                        <div id="lateAppAlertSuccess" class="alert alert-success mt-3 d-none fs-12 py-2" role="alert"></div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 9. History Tab (Leave, Late, Manual, Mobile & Selfie) -->
                <div class="tab-pane fade" id="historyTabContent" role="tabpanel" aria-labelledby="history-tab">
                    <div class="card custom-card shadow-sm">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom py-3">
                            <div>
                                <h6 class="card-title fw-bold text-dark mb-0">
                                    <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Activity & Request History
                                </h6>
                                <small class="text-muted">Comprehensive history of your Leaves, Late Requests, Manual Punches & Mobile/Selfie attendances</small>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="input-group input-group-sm" style="width: 170px;">
                                    <span class="input-group-text bg-light"><i class="fa-solid fa-calendar"></i></span>
                                    <input type="month" class="form-control" id="historyMonthFilter" value="<?= date('Y-m') ?>">
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnRefreshHistory">
                                    <i class="fa-solid fa-arrows-rotate me-1"></i> Refresh
                                </button>
                            </div>
                        </div>

                        <div class="card-body">
                            <!-- Filter Pills -->
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                                <div class="btn-group btn-group-sm flex-wrap" role="group" id="historyCategoryFilters">
                                    <button type="button" class="btn btn-outline-primary active btn-hist-filter" data-filter="all">
                                        <i class="fa-solid fa-list me-1"></i> All Records <span class="badge bg-primary ms-1" id="badgeAllHistory">0</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-hist-filter" data-filter="mobile">
                                        <i class="fa-solid fa-mobile-screen-button text-info me-1"></i> Mobile Punch <span class="badge bg-info ms-1" id="badgeMobileHistory">0</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-hist-filter" data-filter="leave">
                                        <i class="fa-solid fa-umbrella-beach text-success me-1"></i> Leave <span class="badge bg-success ms-1" id="badgeLeaveHistory">0</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-hist-filter" data-filter="late">
                                        <i class="fa-solid fa-clock text-danger me-1"></i> Late Excuse <span class="badge bg-danger ms-1" id="badgeLateHistory">0</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-hist-filter" data-filter="manual">
                                        <i class="fa-solid fa-location-dot text-warning me-1"></i> Manual & Field Visit <span class="badge bg-warning ms-1" id="badgeManualHistory">0</span>
                                    </button>
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    <div class="input-group input-group-sm" style="width: 220px;">
                                        <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass"></i></span>
                                        <input type="text" class="form-control" id="historySearchInput" placeholder="Search history...">
                                    </div>
                                </div>
                            </div>

                            <!-- History Table -->
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered align-middle text-start fs-12 mb-0" id="historyDataTable">
                                    <thead class="table-light text-secondary">
                                        <tr>
                                            <th style="width: 45px;" class="text-center">SN</th>
                                            <th style="width: 110px;">Date</th>
                                            <th style="width: 150px;">Type / Category</th>
                                            <th style="width: 140px;">Time / Duration</th>
                                            <th style="width: 180px;">Location / Site</th>
                                            <th>Reason / Remarks</th>
                                            <th style="width: 120px;" class="text-center">Status</th>
                                            <th style="width: 180px;">Approver Info</th>
                                        </tr>
                                    </thead>
                                    <tbody id="historyTableBody">
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                <span class="spinner-border spinner-border-sm me-2" role="status"></span> Loading history records...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div id="noHistoryMsg" class="alert alert-info text-center d-none mt-3 mb-0">
                                <i class="fa-solid fa-circle-info me-2"></i> No records found for the selected month and filter.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 10. Manual Entry / Missing Punch / Field Visit Tab -->
                <div class="tab-pane fade" id="manualEntryTabContent" role="tabpanel" aria-labelledby="manual-entry-tab">
                    <div class="row justify-content-center">
                        <div class="col-lg-8 col-md-10">
                            <div class="card custom-card shadow-sm border-0">
                                <div class="card-header border-bottom py-3 bg-light">
                                    <h6 class="card-title fw-bold text-dark mb-0">
                                        <i class="fa-solid fa-file-pen text-warning me-2"></i>Apply Manual Entry / Missing Punch / Field Visit
                                    </h6>
                                    <small class="text-muted">Submit attendance correction, missing punch request, or official field visit log</small>
                                </div>
                                <div class="card-body p-4">
                                    <div class="alert alert-danger d-none" id="manualAppAlertError"></div>
                                    <div class="alert alert-success d-none" id="manualAppAlertSuccess"></div>

                                    <form id="formManualEntryApp">
                                        <!-- 1. Main Type Dropdown -->
                                        <div class="mb-3">
                                            <label for="manualAppType" class="form-label fw-bold text-dark">
                                                Application Type <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select form-select-lg fw-semibold" id="manualAppType" required>
                                                <option value="1" selected>Manual Entry</option>
                                                <option value="2">Missing Punch</option>
                                                <option value="3">Field Visit</option>
                                            </select>
                                            <div class="form-text text-muted" id="manualAppTypeHelp">
                                                Full day / customized manual in-time and out-time correction.
                                            </div>
                                        </div>

                                        <!-- 2. Missing Punch Sub-Options (Shown only when Type = 2) -->
                                        <div id="sectionMissingPunch" class="d-none border rounded p-3 mb-3 bg-light-subtle">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="missingPunchSubtype" class="form-label fw-semibold">Punch Source <span class="text-danger">*</span></label>
                                                    <select class="form-select" id="missingPunchSubtype">
                                                        <option value="device" selected>Manual Device Punch</option>
                                                        <option value="mobile">Manual Mobile Punch</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="missingPunchEntryExit" class="form-label fw-semibold">Punch Type <span class="text-danger">*</span></label>
                                                    <select class="form-select" id="missingPunchEntryExit">
                                                        <option value="1" selected>Entry Punch (In Time)</option>
                                                        <option value="2">Exit Punch (Out Time)</option>
                                                    </select>
                                                </div>
                                                <!-- Device Select (For Device Punch) -->
                                                <div class="col-12" id="sectionDeviceSelect">
                                                    <label for="deviceSelect" class="form-label fw-semibold">Select Biometric Device <span class="text-danger">*</span></label>
                                                    <select class="form-select" id="deviceSelect">
                                                        <option value="" selected disabled>Loading devices...</option>
                                                    </select>
                                                </div>
                                                <!-- Location Input (For Mobile Punch) -->
                                                <div class="col-12 d-none" id="sectionMobileLocation">
                                                    <label for="manualMobileLocation" class="form-label fw-semibold">Location / Address <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="manualMobileLocation" placeholder="Enter location name, e.g. Corporate Head Office, Dhaka">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 3. Field Visit Area (Shown only when Type = 3) -->
                                        <div id="sectionFieldVisit" class="d-none border rounded p-3 mb-3 bg-light-subtle">
                                            <label for="fieldVisitAreaInput" class="form-label fw-semibold">Visit Area / Destination <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="fieldVisitAreaInput" placeholder="e.g. Savar Project Site, Gulshan Client Office">
                                            <div class="form-text text-muted">Mention the official site or client destination visited.</div>
                                        </div>

                                        <!-- 4. Date Field -->
                                        <div class="mb-3">
                                            <label for="manualAppDate" class="form-label fw-bold text-dark">
                                                Date <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" class="form-control" id="manualAppDate" value="<?= date('Y-m-d') ?>" required>
                                        </div>

                                        <!-- 5A. Entry and Exit Times (For Manual Entry & Field Visit) -->
                                        <div id="sectionEntryExitTimes" class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <label for="manualEntryTime" class="form-label fw-bold text-dark">
                                                    Entry Time <span class="text-danger">*</span>
                                                </label>
                                                <input type="time" class="form-control" id="manualEntryTime" value="09:30" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="manualExitTime" class="form-label fw-bold text-dark">
                                                    Exit Time <span class="text-danger">*</span>
                                                </label>
                                                <input type="time" class="form-control" id="manualExitTime" value="18:30" required>
                                            </div>
                                        </div>

                                        <!-- 5B. Single Punch Time (For Missing Punch) -->
                                        <div id="sectionSinglePunchTime" class="mb-3 d-none">
                                            <label for="manualSinglePunchTime" class="form-label fw-bold text-dark">
                                                Punch Time <span class="text-danger">*</span>
                                            </label>
                                            <input type="time" class="form-control" id="manualSinglePunchTime" value="09:30">
                                        </div>

                                        <!-- 6. Reason / Comment -->
                                        <div class="mb-4">
                                            <label for="manualAppReason" class="form-label fw-bold text-dark">
                                                Reason / Comment <span class="text-danger">*</span>
                                            </label>
                                            <textarea class="form-control" id="manualAppReason" rows="3" required placeholder="Explain the reason for manual attendance application..."></textarea>
                                        </div>

                                        <!-- Submit Button -->
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="reset" class="btn btn-outline-secondary" id="btnResetManualApp">Reset</button>
                                            <button type="submit" class="btn btn-primary px-4" id="btnSubmitManualApp">
                                                <i class="fa-solid fa-paper-plane me-2"></i> Submit Application
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Selfie Photo Viewer Modal -->
        <div class="modal fade" id="selfiePhotoModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg border-0">
                    <div class="modal-header border-0 pb-0">
                        <h6 class="modal-title fw-bold" id="selfieModalTitle"><i class="fa-solid fa-camera me-2 text-primary"></i>Attendance Selfie</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center p-3">
                        <img id="selfieModalImg" src="" alt="Selfie Photo" class="img-fluid rounded shadow-sm border" style="max-height: 480px; width: auto; object-fit: contain;">
                        <div class="mt-2 text-muted fs-12" id="selfieModalMeta"></div>
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
            .then(async res => {
                const text = await res.text();
                try {
                    return JSON.parse(text);
                } catch(e) {
                    throw new Error('API connection failure: ' + text);
                }
            })
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

                // Update Approvers on Apply Leave Form
                const firstAppEl = document.getElementById("formFirstApproverName");
                const secondAppEl = document.getElementById("formSecondApproverName");
                if (firstAppEl) firstAppEl.textContent = profile.firstApproverName || '—';
                if (secondAppEl) secondAppEl.textContent = profile.secondApproverName || '—';

                // Update Approvers on Late Request Form
                const firstLateAppEl = document.getElementById("formFirstLateApproverName");
                const secondLateAppEl = document.getElementById("formSecondLateApproverName");
                if (firstLateAppEl) firstLateAppEl.textContent = profile.firstLateApproverName || profile.firstApproverName || '—';
                if (secondLateAppEl) secondLateAppEl.textContent = profile.secondLateApproverName || profile.secondApproverName || '—';

                window.cachedLeaveBalances = response.leaveBalances || [];
                if (typeof loadLeaveCategories === 'function') {
                    loadLeaveCategories();
                }
                if (typeof loadTipsoiLateEntries === 'function') {
                    loadTipsoiLateEntries();
                }

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

                // 2.1 Populate Leave History Log Table
                const leaveHistoryTableBody = document.getElementById("leaveHistoryTableBody");
                const noLeaveHistoryMsg = document.getElementById("noLeaveHistoryMsg");
                if (leaveHistoryTableBody) {
                    leaveHistoryTableBody.innerHTML = "";
                    const leaveHistory = response.leaveHistory || [];
                    if (leaveHistory.length === 0) {
                        if (noLeaveHistoryMsg) noLeaveHistoryMsg.classList.remove("d-none");
                    } else {
                        if (noLeaveHistoryMsg) noLeaveHistoryMsg.classList.add("d-none");
                        leaveHistory.forEach(h => {
                            let statusBadge = '<span class="badge bg-warning">Pending</span>';
                            if (h.approvalStatus === 1) {
                                statusBadge = '<span class="badge bg-success">Approved</span>';
                            } else if (h.approvalStatus === 2) {
                                statusBadge = '<span class="badge bg-danger">Rejected</span>';
                            }
                            
                            const datesStr = (h.startDate === h.endDate) ? h.startDate : `${h.startDate} to ${h.endDate}`;
                            
                            leaveHistoryTableBody.innerHTML += `
                                <tr>
                                    <td><strong>${h.categoryName || 'Leave'}</strong><br><small class="text-muted">${h.reason || '—'}</small></td>
                                    <td>${datesStr}</td>
                                    <td>${h.duration || 1} Day(s)</td>
                                    <td>${statusBadge}</td>
                                </tr>
                            `;
                        });
                    }
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

                        let actionBtn = '<span class="text-muted fs-11">N/A</span>';
                        if (p.id && (p.status === 0 || p.status === '0')) {
                            actionBtn = `<button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 btn-cancel-request" data-id="${p.id}" data-type="${p.rawType || 'leave'}" data-req-type="${p.requestType}">
                                <i class="fa-solid fa-ban me-1"></i>Cancel
                            </button>`;
                        }

                        pendingTbody.innerHTML += `
                            <tr>
                                <td class="fw-bold text-dark">${p.requestType}</td>
                                <td>${p.startDate}</td>
                                <td>${p.reason}</td>
                                <td>
                                    ${approverStr || `<span class="badge ${statusBadge}">${statusText}</span>`}
                                </td>
                                <td class="text-center">
                                    ${actionBtn}
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

    // Export Event Listeners (Safely guarded)
    const btnAttendanceExcel = document.getElementById("btnMyAttendanceExcel");
    if (btnAttendanceExcel) {
        btnAttendanceExcel.addEventListener("click", function() {
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
    }

    const btnAttendancePdf = document.getElementById("btnMyAttendancePdf");
    if (btnAttendancePdf) {
        btnAttendancePdf.addEventListener("click", function() {
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
    }
    
    // ==========================================
    // 6. Apply Leave Functionality
    // ==========================================
    const applyLeaveForm = document.getElementById("applyLeaveForm");
    const leaveCategoryId = document.getElementById("leaveCategoryId");
    const leaveStartDate = document.getElementById("leaveStartDate");
    const leaveEndDate = document.getElementById("leaveEndDate");
    const leaveSingleDate = document.getElementById("leaveSingleDate");
    const blockFullDayDates = document.getElementById("blockFullDayDates");
    const blockHalfDayDate = document.getElementById("blockHalfDayDate");
    const leaveModeFull = document.getElementById("leaveModeFull");
    const leaveModeHalf = document.getElementById("leaveModeHalf");
    const leaveDurationDisplay = document.getElementById("leaveDurationDisplay");
    const leaveCategoryBalanceBadge = document.getElementById("leaveCategoryBalanceBadge");
    const leaveBalanceText = document.getElementById("leaveBalanceText");

    let leaveCategoriesList = [];

    // Calculate live leave duration
    function updateLeaveDuration() {
        if (!leaveDurationDisplay) return;

        const isHalf = leaveModeHalf && leaveModeHalf.checked;

        if (isHalf) {
            if (blockFullDayDates) blockFullDayDates.classList.add("d-none");
            if (blockHalfDayDate) blockHalfDayDate.classList.remove("d-none");
            leaveDurationDisplay.textContent = "0.5 Day (Half Day Leave)";
            return;
        }

        if (blockFullDayDates) blockFullDayDates.classList.remove("d-none");
        if (blockHalfDayDate) blockHalfDayDate.classList.add("d-none");

        if (!leaveStartDate || !leaveEndDate) return;

        const sVal = leaveStartDate.value;
        const eVal = leaveEndDate.value;

        if (!sVal || !eVal) {
            leaveDurationDisplay.textContent = "Please select dates";
            return;
        }

        const sDate = new Date(sVal + 'T00:00:00');
        const eDate = new Date(eVal + 'T00:00:00');

        if (eDate < sDate) {
            leaveDurationDisplay.innerHTML = '<span class="text-danger">End date cannot be earlier than start date</span>';
            return;
        }

        const diffTime = eDate.getTime() - sDate.getTime();
        const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24)) + 1;
        leaveDurationDisplay.textContent = `${diffDays} Day${diffDays > 1 ? 's' : ''}`;
    }

    // Leave mode radio toggle
    document.querySelectorAll('input[name="leaveModeRadio"]').forEach(radio => {
        radio.addEventListener("change", function() {
            updateLeaveDuration();
        });
    });

    if (leaveStartDate) leaveStartDate.addEventListener("change", updateLeaveDuration);
    if (leaveEndDate) leaveEndDate.addEventListener("change", updateLeaveDuration);
    if (leaveSingleDate) leaveSingleDate.addEventListener("change", function() {
        if (leaveStartDate) leaveStartDate.value = this.value;
        if (leaveEndDate) leaveEndDate.value = this.value;
        updateLeaveDuration();
    });

    // Fetch and populate leave categories with balance tags
    function loadLeaveCategories() {
        if (!leaveCategoryId) return;

        fetch(`${baseUrl}/api/hrm/leave-category`)
            .then(res => res.json())
            .then(data => {
                const list = data.leaveCategoryList || data.data || data;
                if (Array.isArray(list)) {
                    leaveCategoriesList = list;
                    const balances = window.cachedLeaveBalances || [];

                    let html = '<option value="" disabled selected>Select Leave Category...</option>';
                    list.forEach(cat => {
                        if (cat.status === undefined || cat.status == 1 || cat.status === true) {
                            let balText = '';
                            const matchedBal = balances.find(b => 
                                (b.leaveCategoryId && b.leaveCategoryId == cat.id) || 
                                (b.leaveCategoryName && b.leaveCategoryName.toLowerCase().trim() === cat.name.toLowerCase().trim())
                            );

                            if (matchedBal) {
                                const total = parseInt(matchedBal.entitled !== undefined ? matchedBal.entitled : (matchedBal.allocation || matchedBal.totalDays || 0));
                                const remaining = parseInt(matchedBal.balance !== null ? matchedBal.balance : total);
                                balText = ` (Remaining: ${remaining} days)`;
                            }

                            html += `<option value="${cat.id}" data-category-name="${cat.name}">${cat.name}${balText}</option>`;
                        }
                    });
                    leaveCategoryId.innerHTML = html;
                } else {
                    leaveCategoryId.innerHTML = '<option value="" disabled selected>No categories available</option>';
                }
            })
            .catch(err => {
                console.error("Failed to load leave categories", err);
                if (leaveCategoryId) {
                    leaveCategoryId.innerHTML = '<option value="" disabled selected>Error loading categories</option>';
                }
            });
    }

    // Category change handler to show balance badge
    if (leaveCategoryId) {
        leaveCategoryId.addEventListener("change", function() {
            const selectedOpt = this.options[this.selectedIndex];
            const catName = selectedOpt.getAttribute("data-category-name") || selectedOpt.textContent;
            const balances = window.cachedLeaveBalances || [];

            const matchedBal = balances.find(b => 
                (b.leaveCategoryId && b.leaveCategoryId == this.value) || 
                (b.leaveCategoryName && b.leaveCategoryName.toLowerCase().trim() === catName.toLowerCase().trim())
            );

            if (matchedBal && leaveCategoryBalanceBadge && leaveBalanceText) {
                const total = parseInt(matchedBal.entitled !== undefined ? matchedBal.entitled : (matchedBal.allocation || matchedBal.totalDays || 0));
                const remaining = parseInt(matchedBal.balance !== null ? matchedBal.balance : total);
                const used = parseInt(matchedBal.used !== undefined ? matchedBal.used : Math.max(0, total - remaining));
                leaveBalanceText.innerHTML = `<i class="fa-solid fa-circle-info me-1"></i><strong>${catName}:</strong> ${remaining} Days Remaining (${used}/${total} Used)`;
                leaveCategoryBalanceBadge.classList.remove("d-none");
            } else if (leaveCategoryBalanceBadge) {
                leaveCategoryBalanceBadge.classList.add("d-none");
            }
        });
    }

    // Initialize
    loadLeaveCategories();
    updateLeaveDuration();

    // Form Reset Handler
    const btnResetApplyLeave = document.getElementById("btnResetApplyLeave");
    if (btnResetApplyLeave) {
        btnResetApplyLeave.addEventListener("click", function() {
            setTimeout(() => {
                if (leaveModeFull) leaveModeFull.checked = true;
                if (leaveStartDate) leaveStartDate.value = '<?= date('Y-m-d') ?>';
                if (leaveEndDate) leaveEndDate.value = '<?= date('Y-m-d') ?>';
                if (leaveSingleDate) leaveSingleDate.value = '<?= date('Y-m-d') ?>';
                updateLeaveDuration();
                if (leaveCategoryBalanceBadge) leaveCategoryBalanceBadge.classList.add("d-none");
            }, 50);
        });
    }

    // Form Submit
    if (applyLeaveForm) {
        applyLeaveForm.addEventListener("submit", function(e) {
            e.preventDefault();
            
            const btnSubmit = document.getElementById("btnApplyLeave");
            const originalBtnHtml = btnSubmit.innerHTML;
            const errorAlert = document.getElementById("applyLeaveError");
            const successAlert = document.getElementById("applyLeaveSuccess");
            
            errorAlert.classList.add("d-none");
            successAlert.classList.add("d-none");

            const isHalf = leaveModeHalf && leaveModeHalf.checked;
            let startDateVal = leaveStartDate.value;
            let endDateVal = leaveEndDate.value;

            if (isHalf) {
                startDateVal = leaveSingleDate.value;
                endDateVal = leaveSingleDate.value;
            }
            
            if (!startDateVal || !endDateVal) {
                errorAlert.textContent = "Please choose a valid date.";
                errorAlert.classList.remove("d-none");
                return;
            }

            if (!isHalf && (new Date(endDateVal) < new Date(startDateVal))) {
                errorAlert.textContent = "End date cannot be earlier than start date.";
                errorAlert.classList.remove("d-none");
                return;
            }
            
            // Get Tipsoi Employee ID (safely with session fallback)
            const tipsoiEmpId = document.getElementById("pOfficeId") ? document.getElementById("pOfficeId").getAttribute("data-tipsoi-id") : '';

            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Submitting...';

            const startYear = new Date(startDateVal).getFullYear();
            const fiscalYearStartDate = new Date(startYear, 0, 1, 0, 0, 0).getTime();
            const fiscalYearEndDate = new Date(startYear, 11, 31, 23, 59, 59).getTime();

            const startDateMs = new Date(startDateVal + 'T00:00:00').getTime();
            const endDateMs = new Date(endDateVal + 'T23:59:59').getTime();

            const postBodyData = {
                leaveCategoryId: parseInt(leaveCategoryId.value),
                reason: document.getElementById("leaveReason").value.trim(),
                forceApproval: false,
                fiscalYearStartDate: fiscalYearStartDate,
                fiscalYearEndDate: fiscalYearEndDate,
                startDate: startDateMs,
                endDate: endDateMs,
                isHalfDay: isHalf
            };

            const formData = new FormData();
            formData.append('postBody', JSON.stringify(postBodyData));
            
            const fileInput = document.getElementById("leaveAttachment");
            if (fileInput && fileInput.files.length > 0) {
                formData.append('file', fileInput.files[0]);
            }

            const targetUrl = tipsoiEmpId ? `${baseUrl}/api/hrm/leave-management/apply/employee/${tipsoiEmpId}` : `${baseUrl}/api/hrm/leave-management/apply/employee/0`;

            fetch(targetUrl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success || data.message === "Success" || (data.code && data.code === 200)) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Submitted!',
                            text: data.message || 'Leave application submitted successfully!'
                        });
                    }
                    successAlert.textContent = data.message || "Leave application submitted successfully!";
                    successAlert.classList.remove("d-none");
                    applyLeaveForm.reset();
                    if (leaveModeFull) leaveModeFull.checked = true;
                    leaveStartDate.value = '<?= date('Y-m-d') ?>';
                    leaveEndDate.value = '<?= date('Y-m-d') ?>';
                    leaveSingleDate.value = '<?= date('Y-m-d') ?>';
                    updateLeaveDuration();
                    if (leaveCategoryBalanceBadge) leaveCategoryBalanceBadge.classList.add("d-none");
                    
                    // Auto-refresh Dashboard, Pending, and History tabs
                    const currentMonth = document.getElementById("selectedMonth") ? document.getElementById("selectedMonth").value : '<?= date('Y-m') ?>';
                    fetchTipsoiDashboardData(currentMonth);
                    if (typeof fetchHistoryData === 'function') {
                        const m = historyMonthFilter ? historyMonthFilter.value : '<?= date('Y-m') ?>';
                        fetchHistoryData(m);
                    }
                    document.getElementById("btnRefreshApprovals")?.click();
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
                btnSubmit.innerHTML = originalBtnHtml;
            });
        });
    }
    // ==========================================
    // 7. Late Request (Late Arrival Excuse) Logic
    // ==========================================
    const formLateArrivalApp = document.getElementById("formLateArrivalApp");
    const lateEntrySelect = document.getElementById("lateEntrySelect");
    const btnToggleCustomDate = document.getElementById("btnToggleCustomDate");
    const containerSmartLateSelect = document.getElementById("containerSmartLateSelect");
    const containerCustomLateDate = document.getElementById("containerCustomLateDate");
    const customLateDate = document.getElementById("customLateDate");
    const lateDetailsInfoCard = document.getElementById("lateDetailsInfoCard");
    const lateShiftStartTime = document.getElementById("lateShiftStartTime");
    const lateActualPunchTime = document.getElementById("lateActualPunchTime");
    const lateDurationText = document.getElementById("lateDurationText");
    const lateReasonText = document.getElementById("lateReasonText");
    const lateAppAlertError = document.getElementById("lateAppAlertError");
    const lateAppAlertSuccess = document.getElementById("lateAppAlertSuccess");
    const btnResetLateApp = document.getElementById("btnResetLateApp");
    const btnSubmitLateApp = document.getElementById("btnSubmitLateApp");

    let lateEntriesList = [];
    let isCustomDateMode = false;

    // Toggle custom date picker vs smart entries dropdown
    if (btnToggleCustomDate) {
        btnToggleCustomDate.addEventListener("click", function() {
            isCustomDateMode = !isCustomDateMode;
            if (isCustomDateMode) {
                containerSmartLateSelect.classList.add("d-none");
                containerCustomLateDate.classList.remove("d-none");
                lateEntrySelect.removeAttribute("required");
                customLateDate.setAttribute("required", "required");
                this.innerHTML = '<i class="fa-solid fa-list-check me-1"></i>Switch to Smart Late Records List';
                if (lateDetailsInfoCard) lateDetailsInfoCard.classList.add("d-none");
            } else {
                containerSmartLateSelect.classList.remove("d-none");
                containerCustomLateDate.classList.add("d-none");
                customLateDate.removeAttribute("required");
                lateEntrySelect.setAttribute("required", "required");
                this.innerHTML = '<i class="fa-solid fa-calendar-day me-1"></i>Switch to Custom Date Picker';
                if (lateDetailsInfoCard) lateDetailsInfoCard.classList.remove("d-none");
                updateLateEntryDetails();
            }
        });
    }

    // Load actual late records from Tipsoi
    function loadTipsoiLateEntries() {
        if (!lateEntrySelect) return;

        const tipsoiEmpId = document.getElementById("pOfficeId") ? document.getElementById("pOfficeId").getAttribute("data-tipsoi-id") : null;
        if (!tipsoiEmpId) {
            lateEntrySelect.innerHTML = '<option value="" disabled selected>Employee ID not found</option>';
            return;
        }

        lateEntrySelect.innerHTML = '<option value="" disabled selected>Loading actual late records...</option>';

        fetch(`${baseUrl}/api/hrm/late-approval-management/late-entries/${tipsoiEmpId}`)
            .then(res => res.json())
            .then(data => {
                const list = data.lateAttendanceRecords || data.data || [];
                if (Array.isArray(list) && list.length > 0) {
                    lateEntriesList = list;
                    let html = '<option value="" disabled selected>Select an unexcused late date (' + list.length + ' found)...</option>';
                    list.forEach((rec, idx) => {
                        const dObj = new Date(rec.attDateInMilliseconds || rec.attendanceDate);
                        const dateStr = dObj.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', weekday: 'short' });
                        const inTime = rec.entryTimeInMillis ? new Date(rec.entryTimeInMillis).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : (rec.shiftStartTime || 'N/A');
                        const lateDuration = rec.lateBy || 'Late';

                        html += `<option value="${rec.attDateInMilliseconds}" data-index="${idx}">${dateStr} — Late by ${lateDuration} (In: ${inTime})</option>`;
                    });
                    lateEntrySelect.innerHTML = html;
                } else {
                    lateEntrySelect.innerHTML = '<option value="" disabled selected>No unexcused late records found in the last 90 days</option>';
                }
            })
            .catch(err => {
                console.error("Failed to load late entries:", err);
                lateEntrySelect.innerHTML = '<option value="" disabled selected>Error loading late records (use custom date)</option>';
            });
    }

    // Update details card when selecting a late record
    function updateLateEntryDetails() {
        if (!lateEntrySelect || isCustomDateMode) return;
        const selectedOpt = lateEntrySelect.options[lateEntrySelect.selectedIndex];
        if (!selectedOpt || selectedOpt.disabled) {
            if (lateShiftStartTime) lateShiftStartTime.textContent = '—';
            if (lateActualPunchTime) lateActualPunchTime.textContent = '—';
            if (lateDurationText) lateDurationText.innerHTML = '<i class="fa-solid fa-clock me-1"></i>Late Duration: —';
            return;
        }

        const idx = selectedOpt.getAttribute("data-index");
        const rec = lateEntriesList[idx];
        if (rec) {
            if (lateShiftStartTime) lateShiftStartTime.textContent = rec.shiftStartTime || '—';
            if (lateActualPunchTime) {
                lateActualPunchTime.textContent = rec.entryTimeInMillis ? new Date(rec.entryTimeInMillis).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '—';
            }
            if (lateDurationText) {
                lateDurationText.innerHTML = `<i class="fa-solid fa-clock me-1"></i>Late By: <strong>${rec.lateBy || '—'}</strong>`;
            }
        }
    }

    if (lateEntrySelect) {
        lateEntrySelect.addEventListener("change", updateLateEntryDetails);
    }

    // Form Reset
    if (btnResetLateApp) {
        btnResetLateApp.addEventListener("click", function() {
            setTimeout(() => {
                if (isCustomDateMode && btnToggleCustomDate) {
                    btnToggleCustomDate.click();
                }
                updateLateEntryDetails();
                if (lateAppAlertError) lateAppAlertError.classList.add("d-none");
                if (lateAppAlertSuccess) lateAppAlertSuccess.classList.add("d-none");
            }, 50);
        });
    }

    // Submit Late Request
    if (formLateArrivalApp) {
        formLateArrivalApp.addEventListener("submit", function(e) {
            e.preventDefault();

            const tipsoiEmpId = document.getElementById("pOfficeId") ? document.getElementById("pOfficeId").getAttribute("data-tipsoi-id") : null;
            if (!tipsoiEmpId) {
                if (lateAppAlertError) {
                    lateAppAlertError.textContent = "Employee ID not found in session.";
                    lateAppAlertError.classList.remove("d-none");
                }
                return;
            }

            let attendanceDateMs = null;

            if (isCustomDateMode) {
                const cVal = customLateDate.value;
                if (!cVal) {
                    if (lateAppAlertError) {
                        lateAppAlertError.textContent = "Please pick a date.";
                        lateAppAlertError.classList.remove("d-none");
                    }
                    return;
                }
                attendanceDateMs = new Date(cVal + 'T00:00:00').getTime();
            } else {
                const sVal = lateEntrySelect.value;
                if (!sVal) {
                    if (lateAppAlertError) {
                        lateAppAlertError.textContent = "Please select a late date from the list.";
                        lateAppAlertError.classList.remove("d-none");
                    }
                    return;
                }
                attendanceDateMs = parseInt(sVal);
            }

            const reason = lateReasonText.value.trim();
            if (!reason) {
                if (lateAppAlertError) {
                    lateAppAlertError.textContent = "Please provide a valid reason.";
                    lateAppAlertError.classList.remove("d-none");
                }
                return;
            }

            if (lateAppAlertError) lateAppAlertError.classList.add("d-none");
            if (lateAppAlertSuccess) lateAppAlertSuccess.classList.add("d-none");

            const originalBtnHtml = btnSubmitLateApp.innerHTML;
            btnSubmitLateApp.disabled = true;
            btnSubmitLateApp.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Submitting...';

            const payload = {
                attendanceDate: attendanceDateMs,
                reason: reason
            };

            fetch(`${baseUrl}/api/hrm/late-approval-management/apply/employee/${tipsoiEmpId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success || data.message === "Success" || (data.code && data.code === 200)) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Submitted!',
                            text: data.message || 'Late arrival excuse submitted successfully!'
                        });
                    }
                    if (lateAppAlertSuccess) {
                        lateAppAlertSuccess.textContent = data.message || "Late arrival excuse submitted successfully!";
                        lateAppAlertSuccess.classList.remove("d-none");
                    }
                    formLateArrivalApp.reset();
                    loadTipsoiLateEntries();
                    updateLateEntryDetails();

                    // Auto refresh History and Pending tabs
                    if (typeof fetchHistoryData === 'function') {
                        const m = historyMonthFilter ? historyMonthFilter.value : '<?= date('Y-m') ?>';
                        fetchHistoryData(m);
                    }
                    document.getElementById("btnRefreshApprovals")?.click();
                } else {
                    if (lateAppAlertError) {
                        lateAppAlertError.textContent = data.message || "Failed to submit late request.";
                        lateAppAlertError.classList.remove("d-none");
                    }
                }
            })
            .catch(err => {
                console.error("Error submitting late request:", err);
                if (lateAppAlertError) {
                    lateAppAlertError.textContent = "A network error occurred while submitting.";
                    lateAppAlertError.classList.remove("d-none");
                }
            })
            .finally(() => {
                btnSubmitLateApp.disabled = false;
                btnSubmitLateApp.innerHTML = originalBtnHtml;
            });
        });
    }

    // Initial tab click listener
    const lateRequestTab = document.getElementById("late-request-tab");
    if (lateRequestTab) {
        lateRequestTab.addEventListener("shown.bs.tab", function () {
            loadTipsoiLateEntries();
        });
    }

    // ==========================================
    // 8. Cancel Request Handler (For Pending Requests)
    // ==========================================
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-cancel-request');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();

        const reqId = btn.getAttribute('data-id');
        const rawType = btn.getAttribute('data-type'); // 'leave', 'late', or 'manual'
        const reqTypeName = btn.getAttribute('data-req-type') || 'Request';

        if (!reqId) {
            if (window.Swal) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Request ID not found.' });
            } else {
                alert('Request ID not found.');
            }
            return;
        }

        const confirmAction = () => {
            let cancelUrl = '';
            let payload = null;

            if (rawType === 'leave') {
                cancelUrl = `${baseUrl}/api/hrm/leave-management/cancel/${reqId}`;
                payload = { cancellationReason: 'User cancelled leave request' };
            } else if (rawType === 'late') {
                cancelUrl = `${baseUrl}/api/hrm/late-approval-management/cancel/${reqId}`;
                payload = { approverComment: 'User cancelled late excuse request' };
            } else if (rawType === 'manual') {
                cancelUrl = `${baseUrl}/api/hrm/manual-entry/cancel/${reqId}`;
                payload = {};
            } else {
                cancelUrl = `${baseUrl}/api/hrm/leave-management/cancel/${reqId}`;
                payload = { cancellationReason: 'User cancelled request' };
            }

            btn.disabled = true;
            const origHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

            fetch(cancelUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: payload ? JSON.stringify(payload) : undefined
            })
            .then(res => res.json())
            .then(data => {
                if (data.success || data.message === 'Success' || (data.code && data.code === 200)) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Cancelled!',
                            text: `${reqTypeName} has been cancelled successfully.`
                        });
                    } else {
                        alert(`${reqTypeName} has been cancelled successfully.`);
                    }
                    // Refresh dashboard & history
                    const currentMonth = monthFilter ? monthFilter.value : '<?= date('Y-m') ?>';
                    fetchTipsoiDashboard(currentMonth);
                    if (typeof fetchHistoryData === 'function') {
                        const m = historyMonthFilter ? historyMonthFilter.value : '<?= date('Y-m') ?>';
                        fetchHistoryData(m);
                    }
                    if (typeof loadTipsoiLateEntries === 'function') {
                        loadTipsoiLateEntries();
                    }
                } else {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: data.message || 'Failed to cancel request.'
                        });
                    } else {
                        alert(data.message || 'Failed to cancel request.');
                    }
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }
            })
            .catch(err => {
                console.error('Cancel request error:', err);
                if (window.Swal) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Network error occurred while cancelling.' });
                } else {
                    alert('Network error occurred while cancelling.');
                }
                btn.disabled = false;
                btn.innerHTML = origHtml;
            });
        };

        if (window.Swal) {
            Swal.fire({
                title: 'Cancel Request?',
                text: `Are you sure you want to cancel this ${reqTypeName}? This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Cancel it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    confirmAction();
                }
            });
        } else {
            if (confirm(`Are you sure you want to cancel this ${reqTypeName}?`)) {
                confirmAction();
            }
        }
    });

    // -------------------------------------------------------------------------
    // UNIFIED HISTORY TAB LOGIC (Leave, Late, Manual, Mobile & Selfie)
    // -------------------------------------------------------------------------
    let currentHistoryData = null;
    let currentHistFilter = 'all';

    const historyTab = document.getElementById("history-tab");
    const historyMonthFilter = document.getElementById("historyMonthFilter");
    const btnRefreshHistory = document.getElementById("btnRefreshHistory");
    const historySearchInput = document.getElementById("historySearchInput");

    function fetchHistoryData(month) {
        const tbody = document.getElementById("historyTableBody");
        const noMsg = document.getElementById("noHistoryMsg");
        if (!tbody) return;

        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    <span class="spinner-border spinner-border-sm me-2 text-primary" role="status"></span> Fetching history records for ${month}...
                </td>
            </tr>
        `;
        if (noMsg) noMsg.classList.add("d-none");

        fetch(`${baseUrl}/api/employee/tipsoi-history?month=${month}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    currentHistoryData = res;

                    // Update count badges
                    const counts = res.counts || {};
                    const totalAll = (counts.leaves || 0) + (counts.lates || 0) + (counts.manual || 0) + (counts.mobile || 0);
                    
                    document.getElementById("badgeAllHistory").textContent = totalAll;
                    document.getElementById("badgeMobileHistory").textContent = counts.mobile || 0;
                    document.getElementById("badgeLeaveHistory").textContent = counts.leaves || 0;
                    document.getElementById("badgeLateHistory").textContent = counts.lates || 0;
                    document.getElementById("badgeManualHistory").textContent = counts.manual || 0;

                    renderHistoryTable();
                } else {
                    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">${res.message || 'Failed to load history.'}</td></tr>`;
                }
            })
            .catch(err => {
                console.error("History fetch error:", err);
                tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">Network error loading history.</td></tr>`;
            });
    }

    function renderHistoryTable() {
        const tbody = document.getElementById("historyTableBody");
        const noMsg = document.getElementById("noHistoryMsg");
        if (!tbody || !currentHistoryData) return;

        const searchVal = historySearchInput ? historySearchInput.value.toLowerCase().trim() : '';
        let items = [];

        // 1. Mobile Punches & Selfies
        if (currentHistFilter === 'all' || currentHistFilter === 'mobile') {
            (currentHistoryData.mobilePunches || []).forEach(m => {
                items.push({
                    type: 'mobile',
                    categoryName: 'Mobile Punch',
                    date: m.date,
                    timeDuration: (m.checkInTime && m.checkInTime !== '—' ? `In: ${m.checkInTime}` : '') + (m.checkOutTime && m.checkOutTime !== '—' ? `<br>Out: ${m.checkOutTime}` : ''),
                    locationSite: (m.siteName && m.siteName !== '—' ? `<span class="fw-semibold text-dark">${m.siteName}</span>` : '') + 
                                  (m.location && m.location !== '—' ? `<br><small class="text-muted"><i class="fa-solid fa-location-dot me-1 text-danger"></i>${m.location}</small>` : ''),
                    photoUrl: m.photoUrl,
                    remarks: m.remarks && m.remarks !== '—' ? m.remarks : '<span class="text-muted">—</span>',
                    status: '<span class="badge bg-info-subtle text-info fw-semibold"><i class="fa-solid fa-circle-check me-1"></i>Recorded</span>',
                    approverInfo: '<span class="text-muted">—</span>',
                    rawDate: m.date
                });
            });
        }

        // 2. Leave History
        if (currentHistFilter === 'all' || currentHistFilter === 'leave') {
            (currentHistoryData.leaves || []).forEach(l => {
                let statusBadge = '<span class="badge bg-warning text-dark"><i class="fa-solid fa-hourglass-half me-1"></i>Pending</span>';
                if (l.approvalStatus === 1) statusBadge = '<span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i>Approved</span>';
                else if (l.approvalStatus === 2) statusBadge = '<span class="badge bg-danger"><i class="fa-solid fa-circle-xmark me-1"></i>Rejected</span>';
                else if (l.approvalStatus === 3) statusBadge = '<span class="badge bg-secondary"><i class="fa-solid fa-ban me-1"></i>Cancelled</span>';

                if (l.id && (l.approvalStatus === 0 || l.approvalStatus === '0')) {
                    statusBadge += `<br><button type="button" class="btn btn-outline-danger btn-xs py-0 px-2 mt-1 btn-cancel-request" style="font-size: 11px;" data-id="${l.id}" data-type="leave" data-req-type="Leave Application"><i class="fa-solid fa-ban me-1"></i>Cancel</button>`;
                }

                const datePeriod = (l.startDate === l.endDate) ? l.startDate : `${l.startDate} to ${l.endDate}`;
                const approverText = (l.firstApprover || l.secondApprover) ? `1st: ${l.firstApprover || '—'}<br>2nd: ${l.secondApprover || '—'}` : '<span class="text-muted">—</span>';

                items.push({
                    type: 'leave',
                    categoryName: `Leave (${l.categoryName})`,
                    date: datePeriod,
                    timeDuration: `${l.duration} Day(s)`,
                    locationSite: '<span class="text-muted">—</span>',
                    photoUrl: null,
                    remarks: l.reason || '<span class="text-muted">—</span>',
                    status: statusBadge,
                    approverInfo: approverText,
                    rawDate: l.startDate
                });
            });
        }

        // 3. Late Requests
        if (currentHistFilter === 'all' || currentHistFilter === 'late') {
            (currentHistoryData.lates || []).forEach(lt => {
                let statusBadge = '<span class="badge bg-warning text-dark"><i class="fa-solid fa-hourglass-half me-1"></i>Pending</span>';
                if (lt.approvalStatus === 1) statusBadge = '<span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i>Approved</span>';
                else if (lt.approvalStatus === 2) statusBadge = '<span class="badge bg-danger"><i class="fa-solid fa-circle-xmark me-1"></i>Rejected</span>';
                else if (lt.approvalStatus === 3) statusBadge = '<span class="badge bg-secondary"><i class="fa-solid fa-ban me-1"></i>Cancelled</span>';

                if (lt.id && (lt.approvalStatus === 0 || lt.approvalStatus === '0')) {
                    statusBadge += `<br><button type="button" class="btn btn-outline-danger btn-xs py-0 px-2 mt-1 btn-cancel-request" style="font-size: 11px;" data-id="${lt.id}" data-type="late" data-req-type="Late Request"><i class="fa-solid fa-ban me-1"></i>Cancel</button>`;
                }

                const approverText = (lt.firstApprover || lt.secondApprover) ? `1st: ${lt.firstApprover || '—'}<br>2nd: ${lt.secondApprover || '—'}` : '<span class="text-muted">—</span>';

                items.push({
                    type: 'late',
                    categoryName: 'Late Excuse',
                    date: lt.date,
                    timeDuration: `In: ${lt.inTime}<br><small class="text-danger">Late by ${lt.lateBy}</small>`,
                    locationSite: '<span class="text-muted">—</span>',
                    photoUrl: null,
                    remarks: (lt.reason || '—') + (lt.approverComment && lt.approverComment !== '—' ? `<br><small class="text-primary">Comment: ${lt.approverComment}</small>` : ''),
                    status: statusBadge,
                    approverInfo: approverText,
                    rawDate: lt.date
                });
            });
        }

        // 4. Manual Entries & Field Visits
        if (currentHistFilter === 'all' || currentHistFilter === 'manual') {
            (currentHistoryData.manualEntries || []).forEach(mn => {
                let statusBadge = '<span class="badge bg-warning text-dark"><i class="fa-solid fa-hourglass-half me-1"></i>Pending</span>';
                if (mn.approvalStatus === 1) statusBadge = '<span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i>Approved</span>';
                else if (mn.approvalStatus === 2) statusBadge = '<span class="badge bg-danger"><i class="fa-solid fa-circle-xmark me-1"></i>Rejected</span>';
                else if (mn.approvalStatus === 3) statusBadge = '<span class="badge bg-secondary"><i class="fa-solid fa-ban me-1"></i>Cancelled</span>';

                if (mn.id && (mn.approvalStatus === 0 || mn.approvalStatus === '0')) {
                    statusBadge += `<br><button type="button" class="btn btn-outline-danger btn-xs py-0 px-2 mt-1 btn-cancel-request" style="font-size: 11px;" data-id="${mn.id}" data-type="manual" data-req-type="Manual Entry"><i class="fa-solid fa-ban me-1"></i>Cancel</button>`;
                }

                const datePeriod = (mn.startDate === mn.endDate) ? mn.startDate : `${mn.startDate} to ${mn.endDate}`;
                const approverText = (mn.firstApprover || mn.secondApprover) ? `1st: ${mn.firstApprover || '—'}<br>2nd: ${mn.secondApprover || '—'}` : '<span class="text-muted">—</span>';

                items.push({
                    type: 'manual',
                    categoryName: mn.typeName,
                    date: datePeriod,
                    timeDuration: `In: ${mn.entryTime}<br>Out: ${mn.exitTime}`,
                    locationSite: mn.area ? `<span class="fw-semibold text-dark">${mn.area}</span>` : '<span class="text-muted">—</span>',
                    photoUrl: null,
                    remarks: mn.reason || '<span class="text-muted">—</span>',
                    status: statusBadge,
                    approverInfo: approverText,
                    rawDate: mn.startDate
                });
            });
        }

        // Filter by Search Input
        if (searchVal) {
            items = items.filter(it => {
                const combined = (it.categoryName + ' ' + it.date + ' ' + it.remarks + ' ' + it.locationSite).toLowerCase();
                return combined.includes(searchVal);
            });
        }

        if (items.length === 0) {
            tbody.innerHTML = '';
            if (noMsg) noMsg.classList.remove("d-none");
            return;
        }

        if (noMsg) noMsg.classList.add("d-none");

        let sn = 1;
        tbody.innerHTML = items.map(it => {
            let typeBadge = '<span class="badge bg-secondary">Other</span>';
            if (it.type === 'mobile') typeBadge = '<span class="badge bg-info-subtle text-info fw-semibold border border-info-subtle"><i class="fa-solid fa-mobile-screen-button me-1"></i>Mobile Punch</span>';
            else if (it.type === 'leave') typeBadge = '<span class="badge bg-success-subtle text-success fw-semibold border border-success-subtle"><i class="fa-solid fa-umbrella-beach me-1"></i>' + it.categoryName + '</span>';
            else if (it.type === 'late') typeBadge = '<span class="badge bg-danger-subtle text-danger fw-semibold border border-danger-subtle"><i class="fa-solid fa-clock me-1"></i>Late Excuse</span>';
            else if (it.type === 'manual') typeBadge = '<span class="badge bg-warning-subtle text-warning fw-semibold border border-warning-subtle"><i class="fa-solid fa-location-dot me-1"></i>' + it.categoryName + '</span>';

            return `
                <tr>
                    <td class="text-muted fw-semibold text-center">${sn++}</td>
                    <td class="fw-semibold text-dark">${it.date}</td>
                    <td>${typeBadge}</td>
                    <td>${it.timeDuration}</td>
                    <td>${it.locationSite}</td>
                    <td class="text-muted" style="max-width: 260px; word-wrap: break-word;">${it.remarks}</td>
                    <td class="text-center">${it.status}</td>
                    <td class="fs-11 text-muted">${it.approverInfo}</td>
                </tr>
            `;
        }).join('');
    }

    // Category Filter Pills Click Handler
    document.querySelectorAll('#historyCategoryFilters .btn-hist-filter').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#historyCategoryFilters .btn-hist-filter').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentHistFilter = this.getAttribute('data-filter');
            renderHistoryTable();
        });
    });

    if (historySearchInput) {
        historySearchInput.addEventListener('input', renderHistoryTable);
    }

    if (btnRefreshHistory) {
        btnRefreshHistory.addEventListener('click', function() {
            const m = historyMonthFilter ? historyMonthFilter.value : '<?= date('Y-m') ?>';
            fetchHistoryData(m);
        });
    }

    if (historyMonthFilter) {
        historyMonthFilter.addEventListener('change', function() {
            fetchHistoryData(this.value);
        });
    }

    if (historyTab) {
        let historyLoaded = false;
        historyTab.addEventListener('shown.bs.tab', function() {
            if (!historyLoaded) {
                const m = historyMonthFilter ? historyMonthFilter.value : '<?= date('Y-m') ?>';
                fetchHistoryData(m);
                historyLoaded = true;
            }
        });
    }

    if (window.location.hash === '#history') {
        if (historyTab) {
            new bootstrap.Tab(historyTab).show();
        }
    }

    // -------------------------------------------------------------------------
    // MANUAL ENTRY / MISSING PUNCH / FIELD VISIT LOGIC
    // -------------------------------------------------------------------------
    const manualEntryTab = document.getElementById("manual-entry-tab");
    const manualAppType = document.getElementById("manualAppType");
    const manualAppTypeHelp = document.getElementById("manualAppTypeHelp");
    const sectionMissingPunch = document.getElementById("sectionMissingPunch");
    const sectionFieldVisit = document.getElementById("sectionFieldVisit");
    const sectionEntryExitTimes = document.getElementById("sectionEntryExitTimes");
    const sectionSinglePunchTime = document.getElementById("sectionSinglePunchTime");
    const missingPunchSubtype = document.getElementById("missingPunchSubtype");
    const sectionDeviceSelect = document.getElementById("sectionDeviceSelect");
    const sectionMobileLocation = document.getElementById("sectionMobileLocation");
    const deviceSelect = document.getElementById("deviceSelect");
    const formManualEntryApp = document.getElementById("formManualEntryApp");
    const manualAppAlertError = document.getElementById("manualAppAlertError");
    const manualAppAlertSuccess = document.getElementById("manualAppAlertSuccess");

    let biometricDevicesLoaded = false;

    function loadBiometricDevices() {
        if (!deviceSelect || biometricDevicesLoaded) return;
        
        fetch(`${baseUrl}/api/hrm/devices`)
            .then(res => res.json())
            .then(res => {
                if (res.success && Array.isArray(res.devices) && res.devices.length > 0) {
                    biometricDevicesLoaded = true;
                    deviceSelect.innerHTML = res.devices.map(d => 
                        `<option value="${d.id}">${d.location || 'Device'} (${d.identifier || d.id})</option>`
                    ).join('');
                } else {
                    deviceSelect.innerHTML = `<option value="0">Default Device</option>`;
                }
            })
            .catch(err => {
                console.error("Failed to load devices:", err);
                deviceSelect.innerHTML = `<option value="0">Default Device</option>`;
            });
    }

    // Toggle fields based on main dropdown (Type 1, 2, 3)
    if (manualAppType) {
        manualAppType.addEventListener("change", function() {
            const val = this.value;
            manualAppAlertError.classList.add("d-none");
            manualAppAlertSuccess.classList.add("d-none");

            if (val === "1") {
                // Manual Entry (Default)
                sectionMissingPunch.classList.add("d-none");
                sectionFieldVisit.classList.add("d-none");
                sectionEntryExitTimes.classList.remove("d-none");
                sectionSinglePunchTime.classList.add("d-none");
                manualAppTypeHelp.textContent = "Full day / customized manual in-time and out-time correction.";
            } else if (val === "2") {
                // Missing Punch
                sectionMissingPunch.classList.remove("d-none");
                sectionFieldVisit.classList.add("d-none");
                sectionEntryExitTimes.classList.add("d-none");
                sectionSinglePunchTime.classList.remove("d-none");
                manualAppTypeHelp.textContent = "Log a single missing in-punch or out-punch for a device or mobile.";
                loadBiometricDevices();
            } else if (val === "3") {
                // Field Visit
                sectionMissingPunch.classList.add("d-none");
                sectionFieldVisit.classList.remove("d-none");
                sectionEntryExitTimes.classList.remove("d-none");
                sectionSinglePunchTime.classList.add("d-none");
                manualAppTypeHelp.textContent = "Log an official outstation or local client / project site visit.";
            }
        });
    }

    // Toggle device vs mobile sub-fields in Missing Punch
    if (missingPunchSubtype) {
        missingPunchSubtype.addEventListener("change", function() {
            if (this.value === "device") {
                sectionDeviceSelect.classList.remove("d-none");
                sectionMobileLocation.classList.add("d-none");
                loadBiometricDevices();
            } else {
                sectionDeviceSelect.classList.add("d-none");
                sectionMobileLocation.classList.remove("d-none");
            }
        });
    }

    // Handle Form Submit
    if (formManualEntryApp) {
        formManualEntryApp.addEventListener("submit", function(e) {
            e.preventDefault();

            manualAppAlertError.classList.add("d-none");
            manualAppAlertSuccess.classList.add("d-none");

            const btnSubmit = document.getElementById("btnSubmitManualApp");
            const originalBtnHtml = btnSubmit.innerHTML;

            const type = parseInt(manualAppType.value);
            const dateVal = document.getElementById("manualAppDate").value;
            const reasonVal = document.getElementById("manualAppReason").value.trim();

            if (!reasonVal) {
                manualAppAlertError.textContent = "Please provide a reason or comment.";
                manualAppAlertError.classList.remove("d-none");
                return;
            }

            const postData = {
                type: type,
                date: dateVal,
                reason: reasonVal
            };

            if (type === 1) {
                postData.entryTime = document.getElementById("manualEntryTime").value;
                postData.exitTime = document.getElementById("manualExitTime").value;
            } else if (type === 2) {
                postData.missingPunchType = missingPunchSubtype.value;
                postData.entryExitType = parseInt(document.getElementById("missingPunchEntryExit").value);
                postData.punchTime = document.getElementById("manualSinglePunchTime").value;

                if (postData.missingPunchType === 'device') {
                    postData.deviceId = parseInt(deviceSelect.value || 0);
                } else {
                    const loc = document.getElementById("manualMobileLocation").value.trim();
                    if (!loc) {
                        manualAppAlertError.textContent = "Please enter a location or address for mobile punch.";
                        manualAppAlertError.classList.remove("d-none");
                        return;
                    }
                    postData.area = loc;
                }
            } else if (type === 3) {
                postData.entryTime = document.getElementById("manualEntryTime").value;
                postData.exitTime = document.getElementById("manualExitTime").value;
                const areaVal = document.getElementById("fieldVisitAreaInput").value.trim();
                if (!areaVal) {
                    manualAppAlertError.textContent = "Please specify the visit destination or area.";
                    manualAppAlertError.classList.remove("d-none");
                    return;
                }
                postData.area = areaVal;
            }

            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Submitting...';

            fetch(`${baseUrl}/api/employee/manual-entry/apply`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
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
                            text: res.message || 'Application submitted successfully!'
                        });
                    }
                    manualAppAlertSuccess.textContent = res.message || 'Application submitted successfully!';
                    manualAppAlertSuccess.classList.remove("d-none");
                    
                    formManualEntryApp.reset();
                    document.getElementById("manualAppDate").value = '<?= date('Y-m-d') ?>';
                    manualAppType.dispatchEvent(new Event('change'));

                    // Refresh history & pending tabs if loaded
                    if (typeof fetchHistoryData === 'function') {
                        const m = historyMonthFilter ? historyMonthFilter.value : '<?= date('Y-m') ?>';
                        fetchHistoryData(m);
                    }
                    document.getElementById("btnRefreshApprovals")?.click();
                } else {
                    manualAppAlertError.textContent = res.message || 'Failed to submit application.';
                    manualAppAlertError.classList.remove("d-none");
                }
            })
            .catch(err => {
                console.error("Manual entry submission error:", err);
                manualAppAlertError.textContent = 'Network error occurred while submitting.';
                manualAppAlertError.classList.remove("d-none");
            })
            .finally(() => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalBtnHtml;
            });
        });
    }

    if (manualEntryTab) {
        manualEntryTab.addEventListener("shown.bs.tab", function() {
            loadBiometricDevices();
        });
    }

    if (window.location.hash === '#manual-entry') {
        if (manualEntryTab) {
            new bootstrap.Tab(manualEntryTab).show();
        }
    }
});
</script>
