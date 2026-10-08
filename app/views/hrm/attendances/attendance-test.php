<?php
$page_title = "Attendance (Test)";
$page = 'Attendance (Test)';
$show_breadcrumb = true;
?>

<!-- Header Section with Title & Sync Action -->
<div class="row align-items-center mb-25">
    <div class="col-md-7 col-12">
        <h4 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
            <i class="fa-solid fa-clipboard-user text-primary"></i>
            Daily Attendance 
            <span class="badge bg-success-subtle text-success border border-success-subtle fs-12 px-3 py-1 rounded-pill">
                Test Module
            </span>
        </h4>
        <p class="text-muted fs-13 mb-0">Local ERP Attendance Evaluation Engine (powered by raw Tipsoi punch logs)</p>
    </div>
    <div class="col-md-5 col-12 text-md-end mt-3 mt-md-0">
        <button type="button" class="btn btn-primary px-4 fw-semibold shadow-sm" id="btnSyncPunches" onclick="syncPunchesFromTipsoi()">
            <i class="fa-solid fa-rotate me-1" id="syncIcon"></i>
            <span id="syncText">Sync Punches from Tipsoi</span>
        </button>
    </div>
</div>

<!-- FILTER CARD -->
<form method="GET" action="<?= $GLOBALS['base_url'] ?>/attendance-test" class="mb-25">
    <div class="card__wrapper border-0 shadow-sm rounded-4 p-4" style="background: #ffffff;">
        <div class="row g-3 align-items-end">
            <!-- Date Filter -->
            <div class="col-xxl-3 col-xl-3 col-lg-3 col-md-6 col-12">
                <label class="form-label fw-bold text-secondary fs-13 mb-1">
                    <i class="fa-regular fa-calendar me-1"></i> Attendance Date
                </label>
                <input type="date" name="date" id="filterDate" class="form-control form-control-sm rounded-3 py-2" value="<?= htmlspecialchars($selectedDate) ?>">
            </div>

            <!-- Department Filter -->
            <div class="col-xxl-3 col-xl-3 col-lg-3 col-md-6 col-12">
                <label class="form-label fw-bold text-secondary fs-13 mb-1">
                    <i class="fa-regular fa-building me-1"></i> Department
                </label>
                <select name="department_id" class="form-select form-select-sm rounded-3 py-2">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= ($departmentId == $dept['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Search Filter -->
            <div class="col-xxl-3 col-xl-3 col-lg-3 col-md-6 col-12">
                <label class="form-label fw-bold text-secondary fs-13 mb-1">
                    <i class="fa-regular fa-user me-1"></i> Search Employee
                </label>
                <input type="text" name="search" class="form-control form-control-sm rounded-3 py-2" placeholder="Name or Office ID..." value="<?= htmlspecialchars($search) ?>">
            </div>

            <!-- Actions -->
            <div class="col-xxl-3 col-xl-3 col-lg-3 col-md-6 col-12 text-end">
                <div class="d-flex gap-2 justify-content-end">
                    <button type="submit" class="btn btn-dark btn-sm px-4 rounded-3 py-2 fw-semibold">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                    <a href="<?= $GLOBALS['base_url'] ?>/attendance-test" class="btn btn-outline-secondary btn-sm px-3 rounded-3 py-2">
                        <i class="bi bi-arrow-clockwise me-1"></i> Reset
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- SUMMARY STATS CARDS -->
<div class="row g-3 mb-25">
    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-4 col-sm-6 col-6">
        <div class="card__wrapper border-0 shadow-sm rounded-4 p-3 bg-white text-center">
            <span class="fs-12 fw-bold text-muted text-uppercase d-block mb-1">Total Employees</span>
            <h3 class="fw-bold mb-0 text-dark"><?= $summary['total'] ?></h3>
        </div>
    </div>
    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-4 col-sm-6 col-6">
        <div class="card__wrapper border-0 shadow-sm rounded-4 p-3 bg-white text-center border-start border-success border-4">
            <span class="fs-12 fw-bold text-success text-uppercase d-block mb-1">Present</span>
            <h3 class="fw-bold mb-0 text-success"><?= $summary['present'] ?></h3>
        </div>
    </div>
    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-4 col-sm-6 col-6">
        <div class="card__wrapper border-0 shadow-sm rounded-4 p-3 bg-white text-center border-start border-warning border-4">
            <span class="fs-12 fw-bold text-warning text-uppercase d-block mb-1">Late Entry</span>
            <h3 class="fw-bold mb-0 text-warning"><?= $summary['late'] ?></h3>
        </div>
    </div>
    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-4 col-sm-6 col-6">
        <div class="card__wrapper border-0 shadow-sm rounded-4 p-3 bg-white text-center border-start border-danger border-4">
            <span class="fs-12 fw-bold text-danger text-uppercase d-block mb-1">Absent</span>
            <h3 class="fw-bold mb-0 text-danger"><?= $summary['absent'] ?></h3>
        </div>
    </div>
    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-4 col-sm-6 col-6">
        <div class="card__wrapper border-0 shadow-sm rounded-4 p-3 bg-white text-center border-start border-info border-4">
            <span class="fs-12 fw-bold text-info text-uppercase d-block mb-1">On Leave</span>
            <h3 class="fw-bold mb-0 text-info"><?= $summary['leave'] ?></h3>
        </div>
    </div>
    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-4 col-sm-6 col-6">
        <div class="card__wrapper border-0 shadow-sm rounded-4 p-3 bg-white text-center border-start border-secondary border-4">
            <span class="fs-12 fw-bold text-secondary text-uppercase d-block mb-1">Off-Day / Holiday</span>
            <h3 class="fw-bold mb-0 text-secondary"><?= $summary['day_off'] + $summary['holiday'] ?></h3>
        </div>
    </div>
</div>

<!-- ATTENDANCE DATA TABLE -->
<div class="card__wrapper border-0 shadow-sm rounded-4 p-4 bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="attendanceTestTable">
            <thead class="table-light">
                <tr class="text-secondary fs-12 text-uppercase fw-bold">
                    <th class="ps-3 py-3" style="width: 50px;">#</th>
                    <th class="py-3">Employee</th>
                    <th class="py-3">Department</th>
                    <th class="py-3">Shift</th>
                    <th class="py-3 text-center">Punch In</th>
                    <th class="py-3 text-center">Punch Out</th>
                    <th class="py-3 text-center">Late By</th>
                    <th class="py-3 text-center">Working Hours</th>
                    <th class="py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($attendances)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-folder-open fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            No attendance records found for this date. Click <strong>"Sync Punches from Tipsoi"</strong> to pull logs.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $sl = 1; foreach ($attendances as $row): 
                        $status = $row['today_status'];
                        $badgeClass = 'bg-secondary';
                        if ($status === 'Present') $badgeClass = 'bg-success text-white';
                        elseif ($status === 'Late') $badgeClass = 'bg-warning text-dark';
                        elseif ($status === 'Half-Day') $badgeClass = 'bg-warning-subtle text-warning border border-warning';
                        elseif ($status === 'Absent') $badgeClass = 'bg-danger text-white';
                        elseif ($status === 'On Leave') $badgeClass = 'bg-info text-white';
                        elseif ($status === 'Day Off') $badgeClass = 'bg-secondary-subtle text-secondary border';
                        elseif ($status === 'Holiday') $badgeClass = 'bg-dark text-white';

                        $inTimeDisplay = !empty($row['punch_in_time']) ? date('h:i A', strtotime($row['punch_in_time'])) : '—';
                        $outTimeDisplay = !empty($row['punch_out_time']) ? date('h:i A', strtotime($row['punch_out_time'])) : '—';
                    ?>
                        <tr>
                            <td class="ps-3 fw-bold text-muted fs-13"><?= $sl++ ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" 
                                         style="width: 38px; height: 38px; background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%); font-size: 13px;">
                                        <?= strtoupper(substr($row['employee_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark fs-14"><?= htmlspecialchars($row['employee_name']) ?></div>
                                        <div class="fs-12 text-muted">ID: <span class="fw-semibold text-primary"><?= htmlspecialchars($row['office_id'] ?: '—') ?></span></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark fs-13"><?= htmlspecialchars($row['department_name'] ?: '—') ?></div>
                                <div class="fs-12 text-muted"><?= htmlspecialchars($row['designation_name'] ?: '—') ?></div>
                            </td>
                            <td>
                                <?php 
                                    $rowShiftName = $row['shift_name'] ?: 'IT / Head Office Regular';
                                    if ($rowShiftName === 'Friday OFF' || $rowShiftName === 'Saturday OFF') {
                                        $rowShiftName = 'Regular Shift';
                                    }
                                ?>
                                <div class="fw-semibold text-dark fs-13"><?= htmlspecialchars($rowShiftName) ?></div>
                                <div class="fs-12 text-muted">
                                    <i class="fa-regular fa-clock me-1"></i><?= !empty($row['shift_start_time']) ? date('h:i A', strtotime($row['shift_start_time'])) : '09:30 AM' ?>
                                </div>
                            </td>
                            <td class="text-center fw-bold text-dark fs-13">
                                <?= $inTimeDisplay ?>
                            </td>
                            <td class="text-center fw-bold text-dark fs-13">
                                <?= $outTimeDisplay ?>
                            </td>
                            <td class="text-center fs-13">
                                <?php if (!empty($row['late_minutes']) && $row['late_minutes'] > 0): ?>
                                    <span class="badge bg-warning-subtle text-danger border border-warning-subtle fw-semibold px-2 py-1">
                                        +<?= $row['late_minutes'] ?> mins
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-semibold text-dark fs-13">
                                <?= htmlspecialchars($row['working_hours'] ?: '—') ?>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill px-3 py-1 fs-12 fw-bold <?= $badgeClass ?>">
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function syncPunchesFromTipsoi() {
    const dateInput = document.getElementById('filterDate');
    const selectedDate = dateInput ? dateInput.value : '<?= date('Y-m-d') ?>';
    const baseUrl = '<?= $GLOBALS['base_url'] ?>';

    const btn = document.getElementById('btnSyncPunches');
    const icon = document.getElementById('syncIcon');
    const text = document.getElementById('syncText');

    if (btn) btn.disabled = true;
    if (icon) icon.classList.add('fa-spin');
    if (text) text.textContent = 'Syncing from Tipsoi...';

    const formData = new FormData();
    formData.append('date', selectedDate);

    fetch(`${baseUrl}/api/attendance-test/sync`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Sync Completed!',
                text: data.message || `Attendance synced for ${selectedDate}`,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Sync Failed',
                text: data.message || 'Unable to sync attendance from Tipsoi API.'
            });
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'An error occurred while contacting the server.'
        });
    })
    .finally(() => {
        if (btn) btn.disabled = false;
        if (icon) icon.classList.remove('fa-spin');
        if (text) text.textContent = 'Sync Punches from Tipsoi';
    });
}
</script>
