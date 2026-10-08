<?php
$page_title = 'HR ATS - Candidate Tracker';
$baseUrl = $GLOBALS['base_url'];
?>

<div class="container-fluid py-3 px-4">
    <!-- Header Section -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-address-book text-primary"></i>
                Candidate Tracker (ATS)
            </h4>
            <p class="text-muted small mb-0">Track applicants, interview appointments, call outcomes, and hiring pipeline.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
            <button type="button" class="btn btn-outline-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#csvUploadModal">
                <i class="fa-solid fa-file-csv me-1"></i> Batch CSV Upload
            </button>
            <a href="<?= $baseUrl ?>/ats-dashboard" class="btn btn-outline-info btn-sm px-3">
                <i class="fa-solid fa-chart-pie me-1"></i> Recruitment Dashboard
            </a>
            <a href="<?= $baseUrl ?>/ats-dropdown-settings" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fa-solid fa-sliders me-1"></i> Dropdown Settings
            </a>
            <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" onclick="openAddModal()">
                <i class="fa-solid fa-plus me-1"></i> Add Candidate
            </button>
        </div>
    </div>

    <!-- Flash Alerts -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show py-2 px-3 small" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Summary Statistics Cards -->
    <div class="row mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= $baseUrl ?>/ats-tracker?tab=all" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 p-2 h-100 <?= ($filters['tab'] === 'all') ? 'border-primary border-2 bg-light' : '' ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Total Applicants</span>
                            <h4 class="fw-bold my-0 text-dark"><?= (int)($tabCounts['all'] ?? 0) ?></h4>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                            <i class="fa-solid fa-users fs-5"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= $baseUrl ?>/ats-tracker?tab=calling_queue" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 p-2 h-100 <?= ($filters['tab'] === 'calling_queue') ? 'border-warning border-2 bg-light' : '' ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Calling Queue</span>
                            <h4 class="fw-bold my-0 text-warning"><?= (int)($tabCounts['calling_queue'] ?? 0) ?></h4>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(245, 158, 11, 0.15); color: #d97706;">
                            <i class="fa-solid fa-phone-volume fs-5"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= $baseUrl ?>/ats-tracker?tab=today" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 p-2 h-100 <?= ($filters['tab'] === 'today') ? 'border-danger border-2 bg-light' : '' ?>" style="background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Today's Interview</span>
                            <h4 class="fw-bold my-0 text-danger"><?= (int)($tabCounts['today'] ?? 0) ?></h4>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(239, 68, 68, 0.12); color: #dc2626;">
                            <i class="fa-solid fa-calendar-day fs-5"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= $baseUrl ?>/ats-tracker?tab=upcoming" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 p-2 h-100 <?= ($filters['tab'] === 'upcoming') ? 'border-info border-2 bg-light' : '' ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Upcoming</span>
                            <h4 class="fw-bold my-0 text-info"><?= (int)($tabCounts['upcoming'] ?? 0) ?></h4>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(2, 132, 199, 0.12); color: #0284c7;">
                            <i class="fa-solid fa-calendar-week fs-5"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= $baseUrl ?>/ats-tracker?tab=shortlisted" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 p-2 h-100 <?= ($filters['tab'] === 'shortlisted') ? 'border-primary border-2 bg-light' : '' ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Attended / Viva</span>
                            <h4 class="fw-bold my-0 text-primary"><?= (int)($tabCounts['shortlisted'] ?? 0) ?></h4>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                            <i class="fa-solid fa-user-check fs-5"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= $baseUrl ?>/ats-tracker?tab=hired" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 p-2 h-100 <?= ($filters['tab'] === 'hired') ? 'border-success border-2 bg-light' : '' ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Hired / Onboard</span>
                            <h4 class="fw-bold my-0 text-success"><?= (int)($tabCounts['hired'] ?? 0) ?></h4>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(16, 185, 129, 0.15); color: #059669;">
                            <i class="fa-solid fa-briefcase fs-5"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3">
            <form method="GET" action="<?= $baseUrl ?>/ats-tracker" id="filterForm">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($filters['tab']) ?>">
                <div class="row align-items-end">
                    <!-- Search input -->
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Search Candidate</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" name="q" class="form-control" placeholder="Search by name, phone, email..." value="<?= htmlspecialchars($filters['search']) ?>">
                        </div>
                    </div>
                    <!-- Department -->
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Department</label>
                        <select name="department" class="form-select form-select-sm">
                            <option value="">All Departments</option>
                            <?php foreach ($options['department'] ?? [] as $dept): ?>
                                <option value="<?= htmlspecialchars($dept['option_value']) ?>" <?= ($filters['department'] === $dept['option_value']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['option_value']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Position -->
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Position</label>
                        <select name="position" class="form-select form-select-sm">
                            <option value="">All Positions</option>
                            <?php foreach ($options['position'] ?? [] as $pos): ?>
                                <option value="<?= htmlspecialchars($pos['option_value']) ?>" <?= ($filters['position'] === $pos['option_value']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pos['option_value']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Call / Invitation Status -->
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Call Status</label>
                        <select name="invitation_status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            <?php foreach ($options['invitation_status'] ?? [] as $st): ?>
                                <option value="<?= htmlspecialchars($st['option_value']) ?>" <?= ($filters['invitation_status'] === $st['option_value']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($st['option_value']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Attendance Status -->
                    <div class="col-6 col-md-1">
                        <label class="form-label small fw-semibold text-muted mb-1">Attendance</label>
                        <select name="interview_attendance" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="pending" <?= ($filters['interview_attendance'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="present" <?= ($filters['interview_attendance'] === 'present') ? 'selected' : '' ?>>Present</option>
                            <option value="absent" <?= ($filters['interview_attendance'] === 'absent') ? 'selected' : '' ?>>Absent</option>
                        </select>
                    </div>
                    <!-- Filter Actions -->
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill">
                            <i class="fa-solid fa-filter me-1"></i> Filter
                        </button>
                        <a href="<?= $baseUrl ?>/ats-tracker?tab=<?= htmlspecialchars($filters['tab']) ?>" class="btn btn-light btn-sm text-secondary" title="Clear Filters">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    </div>
                </div>

                <!-- Secondary Filter (Collapsible Date Range) -->
                <div class="row g-3 mt-1 align-items-center">
                    <div class="col-auto">
                        <span class="small fw-semibold text-muted"><i class="fa-regular fa-calendar me-1"></i>Date Range:</span>
                    </div>
                    <div class="col-auto">
                        <select name="date_type" class="form-select form-select-sm py-0" style="font-size:0.75rem; height:28px;">
                            <option value="interview_date" <?= ($filters['date_type'] === 'interview_date') ? 'selected' : '' ?>>Interview Date</option>
                            <option value="interview_call_date" <?= ($filters['date_type'] === 'interview_call_date') ? 'selected' : '' ?>>Call Date</option>
                            <option value="created_at" <?= ($filters['date_type'] === 'created_at') ? 'selected' : '' ?>>Created Date</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <input type="date" name="date_from" class="form-control form-control-sm py-0" style="font-size:0.75rem; height:28px;" value="<?= htmlspecialchars($filters['date_from']) ?>">
                    </div>
                    <div class="col-auto small text-muted">to</div>
                    <div class="col-auto">
                        <input type="date" name="date_to" class="form-control form-control-sm py-0" style="font-size:0.75rem; height:28px;" value="<?= htmlspecialchars($filters['date_to']) ?>">
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Batch Actions Bar (Visible when rows selected) -->
    <div id="batchActionBar" class="alert alert-secondary py-2 px-3 mb-2 rounded-3 d-none align-items-center justify-content-between shadow-sm">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary rounded-pill px-2" id="selectedCountBadge">0</span>
            <span class="small fw-semibold text-dark">candidate(s) selected</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-dark btn-sm py-1" onclick="openBulkUpdateModal('invitation_status')">
                <i class="fa-solid fa-phone me-1"></i> Change Call Status
            </button>
            <button type="button" class="btn btn-outline-dark btn-sm py-1" onclick="openBulkUpdateModal('interview_attendance')">
                <i class="fa-solid fa-user-check me-1"></i> Change Attendance
            </button>
            <button type="button" class="btn btn-outline-dark btn-sm py-1" onclick="openBulkUpdateModal('schedule')">
                <i class="fa-solid fa-calendar me-1"></i> Set Interview Date
            </button>
            <button type="button" class="btn btn-outline-dark btn-sm py-1" onclick="openBulkUpdateModal('hired')">
                <i class="fa-solid fa-briefcase me-1"></i> Change Hired Status
            </button>
        </div>
    </div>

    <!-- Main Spreadsheet Table -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive" style="max-height: 650px;">
            <table class="table table-hover align-middle mb-0" id="candidatesTable" style="font-size: 0.85rem;">
                <thead class="table-light sticky-top shadow-sm" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.03em;">
                    <tr class="text-secondary">
                        <th class="text-center px-2" style="width: 35px;">
                            <input type="checkbox" class="form-check-input" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
                        </th>
                        <th class="px-2" style="width: 40px;">#</th>
                        <th style="min-width: 220px;">Candidate Details</th>
                        <th style="min-width: 170px;">Position & Dept</th>
                        <th style="min-width: 130px;">Call Status</th>
                        <th style="min-width: 180px;">Interview Schedule</th>
                        <th style="min-width: 130px;">Attendance</th>
                        <th style="min-width: 120px;">Hired / Status</th>
                        <th style="min-width: 110px;">CV / File</th>
                        <th class="text-end px-3" style="width: 90px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($candidates)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="fa-regular fa-folder-open fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                No candidates found matching your criteria.
                                <div class="mt-2">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="openAddModal()">
                                        <i class="fa-solid fa-plus me-1"></i> Add First Candidate
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $sl = ($currentPage - 1) * $perPage;
                        foreach ($candidates as $c): 
                            $sl++;
                            $isToday = !empty($c['interview_date']) && $c['interview_date'] === date('Y-m-d');
                            $isPast = !empty($c['interview_date']) && $c['interview_date'] < date('Y-m-d');
                        ?>
                            <tr id="candidateRow_<?= $c['id'] ?>" class="<?= $isToday ? 'table-warning bg-opacity-25' : '' ?>">
                                <!-- Checkbox -->
                                <td class="text-center px-2">
                                    <input type="checkbox" class="form-check-input row-select-box" value="<?= $c['id'] ?>" onchange="handleRowSelect()">
                                </td>
                                
                                <!-- Serial -->
                                <td class="text-muted fw-semibold px-2"><?= $sl ?></td>

                                <!-- Candidate Name & Contact -->
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($c['name']) ?></div>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <a href="tel:<?= htmlspecialchars($c['phone']) ?>" class="text-decoration-none text-primary small" title="Call">
                                            <i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($c['phone']) ?>
                                        </a>
                                        <a href="https://wa.me/88<?= preg_replace('/[^0-9]/', '', $c['phone']) ?>" target="_blank" class="text-success small" title="WhatsApp Message">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    </div>
                                    <div class="text-muted small" style="font-size: 0.75rem;">
                                        <i class="fa-regular fa-envelope me-1"></i><?= htmlspecialchars($c['email']) ?>
                                    </div>
                                </td>

                                <!-- Position & Department -->
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($c['position']) ?></div>
                                    <span class="badge bg-light text-secondary border mt-1" style="font-size: 0.7rem;">
                                        <?= htmlspecialchars($c['department']) ?>
                                    </span>
                                    <?php if (!empty($c['source'])): ?>
                                        <div class="small text-muted mt-1" style="font-size: 0.72rem;">
                                            <i class="fa-solid fa-bullhorn me-1 opacity-75"></i><?= htmlspecialchars($c['source']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Call / Invitation Status (Quick Dropdown) -->
                                <td>
                                    <select class="form-select form-select-sm py-1 status-select" 
                                            onchange="quickUpdateField(<?= $c['id'] ?>, 'invitation_status', this.value)"
                                            style="font-size: 0.78rem; font-weight: 500;">
                                        <option value="Pending" <?= ($c['invitation_status'] === 'Pending') ? 'selected' : '' ?>>Pending</option>
                                        <?php foreach ($options['invitation_status'] ?? [] as $st): ?>
                                            <option value="<?= htmlspecialchars($st['option_value']) ?>" <?= ($c['invitation_status'] === $st['option_value']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($st['option_value']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (!empty($c['interview_call_date'])): ?>
                                        <div class="text-muted mt-1" style="font-size: 0.7rem;">
                                            <i class="fa-regular fa-calendar-check me-1"></i>Called: <?= date('d M Y', strtotime($c['interview_call_date'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Interview Schedule -->
                                <td>
                                    <?php if (!empty($c['interview_date'])): ?>
                                        <div class="fw-semibold <?= $isToday ? 'text-danger fw-bold' : 'text-dark' ?>">
                                            <i class="fa-regular fa-calendar me-1"></i><?= date('d M Y', strtotime($c['interview_date'])) ?>
                                            <?php if ($isToday): ?>
                                                <span class="badge bg-danger rounded-pill" style="font-size: 0.65rem;">TODAY</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($c['interview_time'])): ?>
                                            <div class="text-muted small" style="font-size: 0.74rem;">
                                                <i class="fa-regular fa-clock me-1"></i><?= date('h:i A', strtotime($c['interview_time'])) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($c['interview_location'])): ?>
                                            <div class="text-secondary small mt-1" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-location-dot me-1 text-danger opacity-75"></i><?= htmlspecialchars($c['interview_location']) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic small">Not scheduled</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Attendance Status (Quick Dropdown) -->
                                <td>
                                    <select class="form-select form-select-sm py-1 attendance-select <?= ($c['interview_attendance'] === 'present') ? 'bg-success text-white' : (($c['interview_attendance'] === 'absent') ? 'bg-danger text-white' : '') ?>" 
                                            onchange="quickUpdateField(<?= $c['id'] ?>, 'interview_attendance', this.value, this)"
                                            style="font-size: 0.78rem; font-weight: 500;">
                                        <option value="pending" <?= ($c['interview_attendance'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                                        <option value="present" <?= ($c['interview_attendance'] === 'present') ? 'selected' : '' ?>>Present</option>
                                        <option value="absent" <?= ($c['interview_attendance'] === 'absent') ? 'selected' : '' ?>>Absent</option>
                                    </select>
                                    <?php if ($isPast && $c['interview_attendance'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark mt-1" style="font-size: 0.65rem;">Awaiting Attendance</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Hired Status -->
                                <td>
                                    <select class="form-select form-select-sm py-1 <?= ($c['hired'] === 'yes') ? 'bg-success text-white' : (($c['hired'] === 'no') ? 'bg-secondary text-white' : '') ?>" 
                                            onchange="quickUpdateField(<?= $c['id'] ?>, 'hired', this.value, this)"
                                            style="font-size: 0.78rem; font-weight: 500;">
                                        <option value="pending" <?= ($c['hired'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                                        <option value="yes" <?= ($c['hired'] === 'yes') ? 'selected' : '' ?>>Hired</option>
                                        <option value="no" <?= ($c['hired'] === 'no') ? 'selected' : '' ?>>Not Selected</option>
                                    </select>
                                    <?php if ($c['hired'] === 'yes' && !empty($c['onboarding_salary'])): ?>
                                        <div class="text-success fw-bold mt-1" style="font-size: 0.7rem;">
                                            ৳ <?= number_format($c['onboarding_salary'], 0) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- CV Document -->
                                <td>
                                    <?php if ($c['cv_source'] === 'file' && !empty($c['cv_file_path'])): ?>
                                        <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.75rem;" onclick="previewCv('<?= $baseUrl ?>/<?= htmlspecialchars($c['cv_file_path']) ?>', '<?= htmlspecialchars(addslashes($c['name'])) ?>')">
                                            <i class="fa-solid fa-file-pdf me-1 text-danger"></i> View CV
                                        </button>
                                    <?php elseif ($c['cv_source'] === 'drive_link' && !empty($c['cv_drive_link'])): ?>
                                        <a href="<?= htmlspecialchars($c['cv_drive_link']) ?>" target="_blank" class="btn btn-outline-info btn-sm py-0 px-2" style="font-size: 0.75rem;">
                                            <i class="fa-brands fa-google-drive me-1 text-warning"></i> Drive Link
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small fst-italic">No CV</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="text-end px-3">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-light btn-sm text-primary" onclick="openEditModal(<?= $c['id'] ?>)" title="Edit Candidate">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Footer / Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white border-0 py-3 d-flex flex-wrap justify-content-between align-items-center">
                <span class="small text-muted">
                    Showing <?= min(($currentPage - 1) * $perPage + 1, $total) ?> to <?= min($currentPage * $perPage, $total) ?> of <?= $total ?> candidates
                </span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php 
                        $queryParams = $_GET;
                        ?>
                        <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                            <?php $queryParams['page'] = $currentPage - 1; ?>
                            <a class="page-link" href="<?= $baseUrl ?>/ats-tracker?<?= http_build_query($queryParams) ?>">Previous</a>
                        </li>
                        <?php for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++): ?>
                            <?php $queryParams['page'] = $p; ?>
                            <li class="page-item <?= ($p === $currentPage) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= $baseUrl ?>/ats-tracker?<?= http_build_query($queryParams) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                            <?php $queryParams['page'] = $currentPage + 1; ?>
                            <a class="page-link" href="<?= $baseUrl ?>/ats-tracker?<?= http_build_query($queryParams) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Single Candidate Add & Edit Form -->
<!-- ========================================================================= -->
<div class="modal fade" id="candidateFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="candidateForm" method="POST" action="<?= $baseUrl ?>/ats-candidate-store" enctype="multipart/form-data">
                <input type="hidden" name="candidate_id" id="formCandidateId" value="">
                
                <div class="modal-header bg-light py-3">
                    <h5 class="modal-title fw-bold" id="candidateModalTitle">Add New Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4">
                    <!-- Core Details (Mandatory) -->
                    <div class="border-bottom pb-3 mb-3">
                        <span class="badge px-3 py-1 mb-2 fw-semibold rounded-pill" style="background: rgba(37, 99, 235, 0.12); color: #1d4ed8; font-size: 0.78rem;">
                            <i class="fa-solid fa-user me-1"></i> 1. Core Information (Mandatory)
                        </span>
                        <div class="row ">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Candidate Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="cName" class="form-control form-control-sm" required placeholder="Full Name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="phone" id="cPhone" class="form-control form-control-sm" required placeholder="017XXXXXXXX">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="cEmail" class="form-control form-control-sm" required placeholder="name@example.com">
                            </div>
                        </div>
                    </div>

                    <!-- Role & Source Details -->
                    <div class="border-bottom pb-3 mb-3">
                        <span class="badge px-3 py-1 mb-2 fw-semibold rounded-pill" style="background: rgba(100, 116, 139, 0.12); color: #334155; font-size: 0.78rem;">
                            <i class="fa-solid fa-briefcase me-1"></i> 2. Position & Source
                        </span>
                        <div class="row ">
                            <!-- Department -->
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Department</label>
                                <select name="department" id="cDepartment" class="form-select form-select-sm" onchange="checkOtherOption('cDepartment', 'cDepartmentOther')">
                                    <option value="">Select Department</option>
                                    <?php foreach ($options['department'] ?? [] as $d): ?>
                                        <option value="<?= htmlspecialchars($d['option_value']) ?>"><?= htmlspecialchars($d['option_value']) ?></option>
                                    <?php endforeach; ?>
                                    <option value="Other">+ Other (Type below)</option>
                                </select>
                                <input type="text" name="department_other" id="cDepartmentOther" class="form-control form-control-sm mt-1 d-none" placeholder="Enter custom department name">
                            </div>
                            <!-- Position -->
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Position / Role</label>
                                <select name="position" id="cPosition" class="form-select form-select-sm" onchange="checkOtherOption('cPosition', 'cPositionOther')">
                                    <option value="">Select Position</option>
                                    <?php foreach ($options['position'] ?? [] as $p): ?>
                                        <option value="<?= htmlspecialchars($p['option_value']) ?>"><?= htmlspecialchars($p['option_value']) ?></option>
                                    <?php endforeach; ?>
                                    <option value="Other">+ Other (Type below)</option>
                                </select>
                                <input type="text" name="position_other" id="cPositionOther" class="form-control form-control-sm mt-1 d-none" placeholder="Enter custom position name">
                            </div>
                            <!-- Source -->
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Source / Channel</label>
                                <select name="source" id="cSource" class="form-select form-select-sm" onchange="checkOtherOption('cSource', 'cSourceOther')">
                                    <option value="">Select Source</option>
                                    <?php foreach ($options['source'] ?? [] as $s): ?>
                                        <option value="<?= htmlspecialchars($s['option_value']) ?>"><?= htmlspecialchars($s['option_value']) ?></option>
                                    <?php endforeach; ?>
                                    <option value="Other">+ Other (Type below)</option>
                                </select>
                                <input type="text" name="source_other" id="cSourceOther" class="form-control form-control-sm mt-1 d-none" placeholder="Enter custom source">
                            </div>
                            <!-- Referred By -->
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Referred By (Optional)</label>
                                <input type="text" name="referred_by" id="cReferredBy" class="form-control form-control-sm" placeholder="Employee / Person name">
                            </div>
                            <!-- Invitation Status -->
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Invitation Call Status</label>
                                <select name="invitation_status" id="cInvitationStatus" class="form-select form-select-sm">
                                    <option value="Pending">Pending</option>
                                    <?php foreach ($options['invitation_status'] ?? [] as $st): ?>
                                        <option value="<?= htmlspecialchars($st['option_value']) ?>"><?= htmlspecialchars($st['option_value']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Interview Scheduling -->
                    <div class="border-bottom pb-3 mb-3">
                        <span class="badge px-3 py-1 mb-2 fw-semibold rounded-pill" style="background: rgba(2, 132, 199, 0.12); color: #0369a1; font-size: 0.78rem;">
                            <i class="fa-solid fa-calendar me-1"></i> 3. Interview Details
                        </span>
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Call Date</label>
                                <input type="date" name="interview_call_date" id="cCallDate" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Interview Date</label>
                                <input type="date" name="interview_date" id="cInterviewDate" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Interview Time</label>
                                <input type="time" name="interview_time" id="cInterviewTime" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Interview Location</label>
                                <select name="interview_location" id="cLocation" class="form-select form-select-sm" onchange="checkOtherOption('cLocation', 'cLocationOther')">
                                    <option value="">Select Location</option>
                                    <?php foreach ($options['interview_location'] ?? [] as $loc): ?>
                                        <option value="<?= htmlspecialchars($loc['option_value']) ?>"><?= htmlspecialchars($loc['option_value']) ?></option>
                                    <?php endforeach; ?>
                                    <option value="Other">+ Other (Type below)</option>
                                </select>
                                <input type="text" name="interview_location_other" id="cLocationOther" class="form-control form-control-sm mt-1 d-none" placeholder="Enter location name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Attendance Outcome</label>
                                <select name="interview_attendance" id="cAttendance" class="form-select form-select-sm">
                                    <option value="pending">Pending</option>
                                    <option value="present">Present</option>
                                    <option value="absent">Absent</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-semibold">Remarks / Notes</label>
                                <input type="text" name="remarks" id="cRemarks" class="form-control form-control-sm" placeholder="Free-form interview notes">
                            </div>
                        </div>
                    </div>

                    <!-- CV Upload / Google Drive Link -->
                    <div class="border-bottom pb-3 mb-3">
                        <span class="badge px-3 py-1 mb-2 fw-semibold rounded-pill" style="background: rgba(16, 185, 129, 0.15); color: #047857; font-size: 0.78rem;">
                            <i class="fa-solid fa-file-pdf me-1"></i> 4. CV / Resume Document
                        </span>
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">CV Source</label>
                                <select name="cv_source" id="cCvSource" class="form-select form-select-sm" onchange="toggleCvSource()">
                                    <option value="file">Upload File (PDF/Word)</option>
                                    <option value="drive_link">Google Drive Link</option>
                                </select>
                            </div>
                            <div class="col-md-8" id="cvFileInputGroup">
                                <label class="form-label small fw-semibold">Attach File (PDF, DOCX)</label>
                                <input type="file" name="cv_file" class="form-control form-control-sm" accept=".pdf,.doc,.docx">
                                <div id="existingCvNotice" class="small text-muted mt-1 d-none"></div>
                            </div>
                            <div class="col-md-8 d-none" id="cvLinkInputGroup">
                                <label class="form-label small fw-semibold">Google Drive / URL Link</label>
                                <input type="url" name="cv_drive_link" id="cCvDriveLink" class="form-control form-control-sm" placeholder="https://drive.google.com/...">
                            </div>
                        </div>
                    </div>

                    <!-- Hiring & Onboarding -->
                    <div>
                        <span class="badge px-3 py-1 mb-2 fw-semibold rounded-pill" style="background: rgba(245, 158, 11, 0.15); color: #b45309; font-size: 0.78rem;">
                            <i class="fa-solid fa-handshake me-1"></i> 5. Hiring & Onboarding Decision
                        </span>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Final Selection (Hired)</label>
                                <select name="hired" id="cHired" class="form-select form-select-sm">
                                    <option value="pending">Decision Pending</option>
                                    <option value="yes">Yes - Selected / Hired</option>
                                    <option value="no">No - Not Selected</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Expected Joining Date</label>
                                <input type="date" name="joining_date" id="cJoiningDate" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Onboarding Salary (BDT)</label>
                                <input type="number" step="0.01" name="onboarding_salary" id="cSalary" class="form-control form-control-sm" placeholder="e.g. 35000">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4" id="submitBtn">Save Candidate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Batch CSV Upload -->
<!-- ========================================================================= -->
<div class="modal fade" id="csvUploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= $baseUrl ?>/ats-csv-upload" enctype="multipart/form-data">
                <div class="modal-header bg-light py-3">
                    <h5 class="modal-title fw-bold">Batch Candidate Entry (CSV)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <i class="fa-solid fa-file-csv text-primary" style="font-size: 3rem;"></i>
                        <h6 class="fw-bold mt-2">Upload Candidate Spreadsheet</h6>
                        <p class="text-muted small">Ideal for walk-in drives or high-volume candidate lists.</p>
                    </div>

                    <div class="alert alert-light border text-start small mb-3">
                        <div class="fw-bold mb-1 text-dark"><i class="fa-solid fa-circle-info text-primary me-1"></i>Required Columns:</div>
                        <code>Name, Phone, Email, Position, Department</code>
                        <div class="text-muted mt-1" style="font-size: 0.75rem;">Other fields (Source, Interview Date, Location, Remarks) can be uploaded or filled later.</div>
                    </div>

                    <div class="mb-3">
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="<?= $baseUrl ?>/ats-sample-csv" class="btn btn-link btn-sm text-decoration-none p-0">
                            <i class="fa-solid fa-download me-1"></i> Download Sample CSV
                        </a>
                        <div>
                            <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm px-3">Upload & Import</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Bulk Action -->
<!-- ========================================================================= -->
<div class="modal fade" id="bulkActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-3">
                <h5 class="modal-title fw-bold" id="bulkActionTitle">Bulk Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="bulkActionType" value="">
                
                <!-- Status Options -->
                <div id="bulkStatusSection" class="d-none">
                    <label class="form-label small fw-semibold">Select Call Status for Selected Candidates</label>
                    <select id="bulkStatusValue" class="form-select">
                        <option value="Yes">Yes (Agreed to interview)</option>
                        <option value="No">No (Declined)</option>
                        <option value="Didn't Pick">Didn't Pick</option>
                        <option value="Busy">Busy</option>
                        <option value="Number Off">Number Off</option>
                        <option value="Will Inform Later">Will Inform Later</option>
                    </select>
                </div>

                <!-- Attendance Options -->
                <div id="bulkAttendanceSection" class="d-none">
                    <label class="form-label small fw-semibold">Select Attendance Outcome</label>
                    <select id="bulkAttendanceValue" class="form-select">
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>

                <!-- Schedule Options -->
                <div id="bulkScheduleSection" class="d-none">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Interview Date</label>
                        <input type="date" id="bulkInterviewDate" class="form-control">
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">Location</label>
                        <select id="bulkInterviewLocation" class="form-select">
                            <option value="">Keep current location</option>
                            <?php foreach ($options['interview_location'] ?? [] as $loc): ?>
                                <option value="<?= htmlspecialchars($loc['option_value']) ?>"><?= htmlspecialchars($loc['option_value']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Hired Options -->
                <div id="bulkHiredSection" class="d-none">
                    <label class="form-label small fw-semibold">Select Final Decision</label>
                    <select id="bulkHiredValue" class="form-select">
                        <option value="yes">Hired / Selected</option>
                        <option value="no">Not Selected</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm px-4" onclick="executeBulkUpdate()">Apply to Selected</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: CV Viewer (PDF Preview) -->
<!-- ========================================================================= -->
<div class="modal fade" id="cvViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="height: 90vh;">
        <div class="modal-content border-0 shadow h-100">
            <div class="modal-header bg-light py-2">
                <h6 class="modal-title fw-bold" id="cvViewerTitle">Candidate CV</h6>
                <div>
                    <a href="#" id="cvDownloadBtn" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2 me-2">
                        <i class="fa-solid fa-download me-1"></i> Open in New Tab
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0 h-100">
                <iframe id="cvViewerFrame" src="" class="w-100 h-100 border-0"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
// =========================================================================
// JavaScript Logic for ATS Tracker
// =========================================================================

// Check "+ Other" selection
function checkOtherOption(selectId, otherInputId) {
    const sel = document.getElementById(selectId);
    const inp = document.getElementById(otherInputId);
    if (sel.value === 'Other') {
        inp.classList.remove('d-none');
        inp.focus();
    } else {
        inp.classList.add('d-none');
        inp.value = '';
    }
}

// Toggle CV file vs link
function toggleCvSource() {
    const src = document.getElementById('cCvSource').value;
    if (src === 'file') {
        document.getElementById('cvFileInputGroup').classList.remove('d-none');
        document.getElementById('cvLinkInputGroup').classList.add('d-none');
    } else {
        document.getElementById('cvFileInputGroup').classList.add('d-none');
        document.getElementById('cvLinkInputGroup').classList.remove('d-none');
    }
}

// Open Add Candidate Modal
function openAddModal() {
    document.getElementById('candidateModalTitle').textContent = 'Add New Candidate';
    document.getElementById('candidateForm').action = '<?= $baseUrl ?>/ats-candidate-store';
    document.getElementById('formCandidateId').value = '';
    document.getElementById('candidateForm').reset();
    document.getElementById('existingCvNotice').classList.add('d-none');
    
    // Hide other inputs
    document.getElementById('cDepartmentOther').classList.add('d-none');
    document.getElementById('cPositionOther').classList.add('d-none');
    document.getElementById('cSourceOther').classList.add('d-none');
    document.getElementById('cLocationOther').classList.add('d-none');
    toggleCvSource();

    new bootstrap.Modal(document.getElementById('candidateFormModal')).show();
}

// Open Edit Candidate Modal
function openEditModal(id) {
    fetch(`<?= $baseUrl ?>/api/ats/candidate-details/${id}`)
        .then(res => res.json())
        .then(res => {
            if (!res.success) {
                Swal.fire('Error', res.message || 'Could not load candidate', 'error');
                return;
            }
            const c = res.data;
            document.getElementById('candidateModalTitle').textContent = 'Edit Candidate: ' + c.name;
            document.getElementById('candidateForm').action = `<?= $baseUrl ?>/ats-candidate-update/${c.id}`;
            document.getElementById('formCandidateId').value = c.id;

            document.getElementById('cName').value = c.name || '';
            document.getElementById('cPhone').value = c.phone || '';
            document.getElementById('cEmail').value = c.email || '';

            // Dropdown values with "Other" check
            setSelectOrOther('cDepartment', 'cDepartmentOther', c.department);
            setSelectOrOther('cPosition', 'cPositionOther', c.position);
            setSelectOrOther('cSource', 'cSourceOther', c.source);
            setSelectOrOther('cLocation', 'cLocationOther', c.interview_location);

            document.getElementById('cReferredBy').value = c.referred_by || '';
            document.getElementById('cInvitationStatus').value = c.invitation_status || 'Pending';
            document.getElementById('cCallDate').value = c.interview_call_date || '';
            document.getElementById('cInterviewDate').value = c.interview_date || '';
            document.getElementById('cInterviewTime').value = c.interview_time || '';
            document.getElementById('cAttendance').value = c.interview_attendance || 'pending';
            document.getElementById('cRemarks').value = c.remarks || '';

            document.getElementById('cCvSource').value = c.cv_source || 'file';
            document.getElementById('cCvDriveLink').value = c.cv_drive_link || '';
            toggleCvSource();

            if (c.cv_file_path) {
                const notice = document.getElementById('existingCvNotice');
                notice.innerHTML = `<i class="fa-solid fa-file-pdf text-danger me-1"></i> Current CV: <a href="<?= $baseUrl ?>/${c.cv_file_path}" target="_blank">View File</a>`;
                notice.classList.remove('d-none');
            } else {
                document.getElementById('existingCvNotice').classList.add('d-none');
            }

            document.getElementById('cHired').value = c.hired || 'pending';
            document.getElementById('cJoiningDate').value = c.joining_date || '';
            document.getElementById('cSalary').value = c.onboarding_salary || '';

            new bootstrap.Modal(document.getElementById('candidateFormModal')).show();
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'Network error loading candidate details', 'error');
        });
}

function setSelectOrOther(selectId, otherInputId, val) {
    const sel = document.getElementById(selectId);
    const inp = document.getElementById(otherInputId);
    if (!val) {
        sel.value = '';
        inp.classList.add('d-none');
        inp.value = '';
        return;
    }
    let found = false;
    for (let opt of sel.options) {
        if (opt.value.toLowerCase() === val.toLowerCase()) {
            sel.value = opt.value;
            found = true;
            break;
        }
    }
    if (!found) {
        sel.value = 'Other';
        inp.classList.remove('d-none');
        inp.value = val;
    } else {
        inp.classList.add('d-none');
        inp.value = '';
    }
}

// 1-Click Fast Inline Update (AJAX)
function quickUpdateField(id, field, value, element = null) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('field', field);
    formData.append('value', value);

    fetch('<?= $baseUrl ?>/api/ats/quick-update', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Server non-JSON response:', text);
            throw new Error('Server returned invalid response');
        }
    })
    .then(data => {
        if (data.success) {
            // Update UI pill color if attendance
            if (element && field === 'interview_attendance') {
                element.classList.remove('bg-success', 'bg-danger', 'text-white');
                if (value === 'present') element.classList.add('bg-success', 'text-white');
                if (value === 'absent') element.classList.add('bg-danger', 'text-white');
            }
            if (element && field === 'hired') {
                element.classList.remove('bg-success', 'bg-secondary', 'text-white');
                if (value === 'yes') element.classList.add('bg-success', 'text-white');
                if (value === 'no') element.classList.add('bg-secondary', 'text-white');
            }
            // Flash toast notification safely
            atsShowToast('Updated successfully!');
        } else {
            Swal.fire('Update Failed', data.message || 'Could not update status', 'error');
        }
    })
    .catch(err => {
        console.error('Quick update error:', err);
        Swal.fire('Error', err.message || 'Network connection issue', 'error');
    });
}

// Preview CV in Modal
function previewCv(url, name) {
    document.getElementById('cvViewerTitle').textContent = 'Resume / CV: ' + name;
    document.getElementById('cvViewerFrame').src = url;
    document.getElementById('cvDownloadBtn').href = url;
    new bootstrap.Modal(document.getElementById('cvViewerModal')).show();
}

// Row Selection & Batch Actions
function toggleSelectAll(master) {
    const boxes = document.querySelectorAll('.row-select-box');
    boxes.forEach(b => b.checked = master.checked);
    handleRowSelect();
}

function handleRowSelect() {
    const selected = document.querySelectorAll('.row-select-box:checked');
    const bar = document.getElementById('batchActionBar');
    const badge = document.getElementById('selectedCountBadge');
    
    if (selected.length > 0) {
        bar.classList.remove('d-none');
        bar.classList.add('d-flex');
        badge.textContent = selected.length;
    } else {
        bar.classList.add('d-none');
        bar.classList.remove('d-flex');
    }
}

function getSelectedIds() {
    const selected = document.querySelectorAll('.row-select-box:checked');
    return Array.from(selected).map(cb => cb.value);
}

function openBulkUpdateModal(actionType) {
    const ids = getSelectedIds();
    if (ids.length === 0) {
        Swal.fire('No Selection', 'Please select at least one candidate.', 'warning');
        return;
    }

    document.getElementById('bulkActionType').value = actionType;
    document.getElementById('bulkStatusSection').classList.add('d-none');
    document.getElementById('bulkAttendanceSection').classList.add('d-none');
    document.getElementById('bulkScheduleSection').classList.add('d-none');
    document.getElementById('bulkHiredSection').classList.add('d-none');

    if (actionType === 'invitation_status') {
        document.getElementById('bulkActionTitle').textContent = `Bulk Call Status (${ids.length} candidates)`;
        document.getElementById('bulkStatusSection').classList.remove('d-none');
    } else if (actionType === 'interview_attendance') {
        document.getElementById('bulkActionTitle').textContent = `Bulk Attendance (${ids.length} candidates)`;
        document.getElementById('bulkAttendanceSection').classList.remove('d-none');
    } else if (actionType === 'schedule') {
        document.getElementById('bulkActionTitle').textContent = `Set Interview Schedule (${ids.length} candidates)`;
        document.getElementById('bulkScheduleSection').classList.remove('d-none');
    } else if (actionType === 'hired') {
        document.getElementById('bulkActionTitle').textContent = `Bulk Hired Decision (${ids.length} candidates)`;
        document.getElementById('bulkHiredSection').classList.remove('d-none');
    }

    new bootstrap.Modal(document.getElementById('bulkActionModal')).show();
}

function executeBulkUpdate() {
    const ids = getSelectedIds();
    const actionType = document.getElementById('bulkActionType').value;
    const formData = new FormData();
    formData.append('action', actionType);
    formData.append('ids', ids.join(','));

    if (actionType === 'invitation_status') {
        formData.append('invitation_status', document.getElementById('bulkStatusValue').value);
    } else if (actionType === 'interview_attendance') {
        formData.append('interview_attendance', document.getElementById('bulkAttendanceValue').value);
    } else if (actionType === 'schedule') {
        formData.append('interview_date', document.getElementById('bulkInterviewDate').value);
        formData.append('interview_location', document.getElementById('bulkInterviewLocation').value);
    } else if (actionType === 'hired') {
        formData.append('hired', document.getElementById('bulkHiredValue').value);
    }

    fetch('<?= $baseUrl ?>/api/ats/bulk-update', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Server non-JSON response:', text);
            throw new Error('Server returned invalid response');
        }
    })
    .then(res => {
        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: res.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error', res.message || 'Bulk update failed', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', err.message || 'Network connection issue', 'error');
    });
}

function atsShowToast(msg) {
    try {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: msg,
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: false,
                backdrop: false
            });
        }
    } catch (e) {
        console.warn('Toast display failed:', e);
    }
}
</script>
