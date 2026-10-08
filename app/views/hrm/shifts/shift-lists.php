<?php
$page_title = "Shift Management (Test)";
$page = 'Shift Management (Test)';
$show_breadcrumb = true;
?>

<!-- Header Section with Add & Assign Actions -->
<div class="row align-items-center mb-25">
    <div class="col-md-7 col-12">
        <h4 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
            <i class="fa-solid fa-business-time text-primary"></i>
            Shift Management
            <span class="badge bg-success-subtle text-success border border-success-subtle fs-12 px-2 py-1 rounded-pill">Test</span>
        </h4>
        <p class="text-muted fs-13 mb-0">Define office working hours, grace periods, and assign shifts to employees</p>
    </div>
    <div class="col-md-5 col-12 text-md-end mt-3 mt-md-0">
        <div class="d-flex gap-2 justify-content-md-end">
            <button type="button" class="btn btn-outline-primary btn-sm px-4 fw-semibold rounded-3" data-bs-toggle="modal" data-bs-target="#assignShiftModal">
                <i class="fa-solid fa-user-clock me-1"></i> Assign Shift
            </button>
            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addShiftModal">
                <i class="fa-solid fa-plus me-1"></i> Add New Shift
            </button>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 mb-20" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-20" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- STATS CARDS -->
<div class="row g-3 mb-25">
    <div class="col-xl-4 col-md-6 col-12">
        <div class="card__wrapper border-0 shadow-sm rounded-4 p-4 bg-white d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white" 
                 style="width: 48px; height: 48px; background: linear-gradient(135deg, #198754 0%, #20c997 100%);">
                <i class="fa-solid fa-clock fs-5"></i>
            </div>
            <div>
                <span class="fs-12 text-muted fw-bold text-uppercase d-block mb-1">Total Shifts</span>
                <h3 class="fw-bold mb-0 text-dark"><?= count($shifts) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 col-12">
        <div class="card__wrapper border-0 shadow-sm rounded-4 p-4 bg-white d-flex align-items-center gap-3 border-start border-success border-4">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-success bg-success-subtle" 
                 style="width: 48px; height: 48px;">
                <i class="fa-solid fa-check-double fs-5"></i>
            </div>
            <div>
                <span class="fs-12 text-success fw-bold text-uppercase d-block mb-1">Active Shifts</span>
                <h3 class="fw-bold mb-0 text-success"><?= count(array_filter($shifts, fn($s) => $s['status'] == 1)) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 col-12">
        <div class="card__wrapper border-0 shadow-sm rounded-4 p-4 bg-white d-flex align-items-center gap-3 border-start border-primary border-4">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-primary bg-primary-subtle" 
                 style="width: 48px; height: 48px;">
                <i class="fa-solid fa-users fs-5"></i>
            </div>
            <div>
                <span class="fs-12 text-primary fw-bold text-uppercase d-block mb-1">Employees Assigned</span>
                <h3 class="fw-bold mb-0 text-primary"><?= array_sum(array_column($shifts, 'assigned_employees_count')) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- TABS: Shifts vs Employee Roster -->
<div class="card__wrapper border-0 shadow-sm rounded-4 p-4 bg-white">
    <ul class="nav nav-pills mb-4" id="shiftTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4 fw-bold fs-13" id="shifts-tab" data-bs-toggle="pill" data-bs-target="#shifts-content" type="button" role="tab">
                <i class="fa-solid fa-layer-group me-1"></i> Configured Shifts (<?= count($shifts) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 fw-bold fs-13" id="roster-tab" data-bs-toggle="pill" data-bs-target="#roster-content" type="button" role="tab">
                <i class="fa-solid fa-users me-1"></i> Employee Shift Assignments (<?= count($employees) ?>)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="shiftTabContent">
        <!-- TAB 1: SHIFTS LIST -->
        <div class="tab-pane fade show active" id="shifts-content" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-secondary fs-12 text-uppercase fw-bold">
                            <th class="ps-3 py-3" style="width: 50px;">#</th>
                            <th class="py-3">Shift Name</th>
                            <th class="py-3 text-center">Start Time</th>
                            <th class="py-3 text-center">End Time</th>
                            <th class="py-3 text-center">Grace Period</th>
                            <th class="py-3 text-center">Half-Day Threshold</th>
                            <th class="py-3 text-center">Assigned Employees</th>
                            <th class="py-3 text-center">Status</th>
                            <th class="py-3 text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($shifts)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">No shifts configured yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php $sl = 1; foreach ($shifts as $s): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-muted fs-13"><?= $sl++ ?></td>
                                    <td>
                                        <div class="fw-bold text-dark fs-14"><?= htmlspecialchars($s['name']) ?></div>
                                    </td>
                                    <td class="text-center fw-bold text-primary fs-13">
                                        <i class="fa-regular fa-clock me-1 opacity-50"></i>
                                        <?= date('h:i A', strtotime($s['start_time'])) ?>
                                    </td>
                                    <td class="text-center fw-bold text-dark fs-13">
                                        <i class="fa-regular fa-clock me-1 opacity-50"></i>
                                        <?= date('h:i A', strtotime($s['end_time'])) ?>
                                    </td>
                                    <td class="text-center fs-13">
                                        <span class="badge bg-warning-subtle text-dark border px-2 py-1 fw-semibold">
                                            <?= $s['grace_minutes'] ?> mins
                                        </span>
                                    </td>
                                    <td class="text-center text-muted fs-13">
                                        <?= !empty($s['half_day_threshold_time']) ? date('h:i A', strtotime($s['half_day_threshold_time'])) : '13:00' ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-light text-primary border px-3 py-1 fw-bold fs-12">
                                            <?= $s['assigned_employees_count'] ?> Employees
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($s['status'] == 1): ?>
                                            <span class="badge bg-success text-white px-2 py-1 fs-11 rounded-pill">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary text-white px-2 py-1 fs-11 rounded-pill">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 me-1" 
                                                onclick='openEditShiftModal(<?= json_encode($s) ?>)'>
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                        <?php if ($s['assigned_employees_count'] == 0 && $s['id'] > 3): ?>
                                            <a href="<?= $GLOBALS['base_url'] ?>/delete-shift/<?= $s['id'] ?>" 
                                               class="btn btn-outline-danger btn-sm px-2 py-1"
                                               onclick="return confirm('Are you sure you want to delete this shift?')">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 2: EMPLOYEE SHIFT ASSIGNMENTS -->
        <div class="tab-pane fade" id="roster-content" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="employeeShiftTable">
                    <thead class="table-light">
                        <tr class="text-secondary fs-12 text-uppercase fw-bold">
                            <th class="ps-3 py-3" style="width: 50px;">#</th>
                            <th class="py-3">Employee</th>
                            <th class="py-3">Department</th>
                            <th class="py-3">Designation</th>
                            <th class="py-3 text-center">Assigned Shift</th>
                            <th class="py-3 text-center">Shift Hours</th>
                            <th class="py-3 text-end pe-3">Change Shift</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $eSl = 1; foreach ($employees as $emp): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-muted fs-13"><?= $eSl++ ?></td>
                                <td>
                                    <div class="fw-bold text-dark fs-14"><?= htmlspecialchars($emp['full_name']) ?></div>
                                    <div class="fs-12 text-muted">ID: <span class="fw-semibold text-primary"><?= htmlspecialchars($emp['employee_id'] ?: '—') ?></span></div>
                                </td>
                                <td class="fs-13 fw-semibold text-dark"><?= htmlspecialchars($emp['department_name'] ?: '—') ?></td>
                                <td class="fs-13 text-muted"><?= htmlspecialchars($emp['designation_name'] ?: '—') ?></td>
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 fs-12 fw-bold">
                                        <?= htmlspecialchars($emp['shift_name'] ?: 'IT / Head Office Regular') ?>
                                    </span>
                                </td>
                                <td class="text-center fs-12 text-muted fw-semibold">
                                    <?= !empty($emp['start_time']) ? date('h:i A', strtotime($emp['start_time'])) . ' - ' . date('h:i A', strtotime($emp['end_time'])) : '09:30 AM - 06:30 PM' ?>
                                </td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary px-3 py-1 rounded-pill fs-12 fw-semibold"
                                            onclick="openQuickAssignModal(<?= $emp['id'] ?>, '<?= htmlspecialchars($emp['full_name'], ENT_QUOTES) ?>', <?= (int)($emp['shift_id'] ?: 1) ?>)">
                                        <i class="fa-solid fa-arrows-rotate me-1"></i> Change
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 1: ADD NEW SHIFT -->
<div class="modal fade" id="addShiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-plus-circle text-primary me-2"></i>Add New Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/store-shift" method="POST">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold fs-13 text-secondary">Shift Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control rounded-3 py-2" placeholder="e.g. Site Engineer Shift" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold fs-13 text-secondary">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control rounded-3 py-2" value="09:30" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold fs-13 text-secondary">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control rounded-3 py-2" value="18:30" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold fs-13 text-secondary">Grace Period (Minutes)</label>
                            <input type="number" name="grace_minutes" class="form-control rounded-3 py-2" value="10" min="0" max="60">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold fs-13 text-secondary">Half-Day Threshold Time</label>
                            <input type="time" name="half_day_threshold_time" class="form-control rounded-3 py-2" value="13:00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-3 fw-bold">Create Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: EDIT SHIFT -->
<div class="modal fade" id="editShiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-regular fa-pen-to-square text-primary me-2"></i>Edit Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editShiftForm" method="POST">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold fs-13 text-secondary">Shift Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editShiftName" class="form-control rounded-3 py-2" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold fs-13 text-secondary">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" id="editShiftStartTime" class="form-control rounded-3 py-2" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold fs-13 text-secondary">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" id="editShiftEndTime" class="form-control rounded-3 py-2" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold fs-13 text-secondary">Grace Period (Minutes)</label>
                            <input type="number" name="grace_minutes" id="editShiftGrace" class="form-control rounded-3 py-2" min="0" max="60">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold fs-13 text-secondary">Half-Day Threshold Time</label>
                            <input type="time" name="half_day_threshold_time" id="editShiftHalfDay" class="form-control rounded-3 py-2">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold fs-13 text-secondary">Status</label>
                        <select name="status" id="editShiftStatus" class="form-select rounded-3 py-2">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-3 fw-bold">Update Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 3: ASSIGN SHIFT (EMPLOYEE OR DEPARTMENT) -->
<div class="modal fade" id="assignShiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-user-clock text-primary me-2"></i>Assign Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/assign-shift" method="POST">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold fs-13 text-secondary">Assignment Mode</label>
                        <div class="d-flex gap-3 mt-1">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="assign_type" id="modeEmp" value="employee" checked onchange="toggleAssignMode()">
                                <label class="form-check-label fw-semibold fs-13" for="modeEmp">Single Employee</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="assign_type" id="modeDept" value="department" onchange="toggleAssignMode()">
                                <label class="form-check-label fw-semibold fs-13" for="modeDept">Entire Department</label>
                            </div>
                        </div>
                    </div>

                    <!-- Single Employee Select -->
                    <div class="mb-3" id="empSelectGroup">
                        <label class="form-label fw-bold fs-13 text-secondary">Select Employee</label>
                        <select name="employee_id" id="assignEmpId" class="form-select rounded-3 py-2">
                            <option value="">-- Choose Employee --</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['employee_id'] ?: 'No ID') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Department Select -->
                    <div class="mb-3 d-none" id="deptSelectGroup">
                        <label class="form-label fw-bold fs-13 text-secondary">Select Department</label>
                        <select name="department_id" class="form-select rounded-3 py-2">
                            <option value="">-- Choose Department --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Shift Select -->
                    <div class="mb-3">
                        <label class="form-label fw-bold fs-13 text-secondary">Target Shift <span class="text-danger">*</span></label>
                        <select name="shift_id" id="assignShiftId" class="form-select rounded-3 py-2" required>
                            <option value="">-- Select Shift --</option>
                            <?php foreach ($shifts as $s): ?>
                                <option value="<?= $s['id'] ?>">
                                    <?= htmlspecialchars($s['name']) ?> (<?= date('h:i A', strtotime($s['start_time'])) ?> - <?= date('h:i A', strtotime($s['end_time'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-3 fw-bold">Save Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditShiftModal(shift) {
    document.getElementById('editShiftForm').action = `<?= $GLOBALS['base_url'] ?>/update-shift/${shift.id}`;
    document.getElementById('editShiftName').value = shift.name;
    document.getElementById('editShiftStartTime').value = shift.start_time.substring(0, 5);
    document.getElementById('editShiftEndTime').value = shift.end_time.substring(0, 5);
    document.getElementById('editShiftGrace').value = shift.grace_minutes;
    document.getElementById('editShiftHalfDay').value = shift.half_day_threshold_time ? shift.half_day_threshold_time.substring(0, 5) : '13:00';
    document.getElementById('editShiftStatus').value = shift.status;

    new bootstrap.Modal(document.getElementById('editShiftModal')).show();
}

function openQuickAssignModal(empId, empName, shiftId) {
    document.getElementById('modeEmp').checked = true;
    toggleAssignMode();
    document.getElementById('assignEmpId').value = empId;
    document.getElementById('assignShiftId').value = shiftId;
    new bootstrap.Modal(document.getElementById('assignShiftModal')).show();
}

function toggleAssignMode() {
    const isDept = document.getElementById('modeDept').checked;
    document.getElementById('deptSelectGroup').classList.toggle('d-none', !isDept);
    document.getElementById('empSelectGroup').classList.toggle('d-none', isDept);
}
</script>
