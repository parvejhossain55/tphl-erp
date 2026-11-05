<?php
$page = "Payroll Workplace";
?>
<style>
.swal2-container { z-index: 1000005 !important; }
</style>
<?php
$show_breadcrumb = true;

$_filterType      = !empty($filterType) ? $filterType : (!empty($_GET['filter_type']) ? $_GET['filter_type'] : 'monthly');
$_fromDate        = !empty($selectedFromDate) ? $selectedFromDate : (!empty($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01'));
$_toDate          = !empty($selectedToDate) ? $selectedToDate : (!empty($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-t'));
$_selectedYear    = !empty($selectedYear) ? $selectedYear : (!empty($_GET['year']) ? $_GET['year'] : date('Y'));
$_selectedMonth   = !empty($selectedMonth) ? $selectedMonth : (!empty($_GET['month']) ? $_GET['month'] : date('m'));
$_selectedProject = !empty($selectedProject) ? $selectedProject : ($_GET['payroll_project_id'] ?? '');

$currentRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
$currentRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
$isManagement = in_array($currentRoleId, [1, 3, 4, 20, 30, 33, 35]) 
             || strpos($currentRoleName, 'admin') !== false 
             || strpos($currentRoleName, 'management') !== false 
             || strpos($currentRoleName, 'hr') !== false;

$selectedProjectObj = null;
if (!empty($payroll_projects) && !empty($_selectedProject)) {
    foreach ($payroll_projects as $pObj) {
        if ((int)$pObj['id'] === (int)$_selectedProject) {
            $selectedProjectObj = $pObj;
            break;
        }
    }
}
$selectedProjectName = $selectedProjectObj['name'] ?? '';
?>

<script>
// Global Vanilla JS functions for Payroll Project deletion (defined upfront before buttons render)
window.confirmDeletePayrollProject = function(projId, projName) {
    projId = parseInt(projId, 10);
    if (!projId) return;
    projName = projName || 'this project';

    var manageModalEl = document.getElementById('manageProjectsModal');
    var wasModalOpen = false;
    if (manageModalEl && (manageModalEl.classList.contains('show') || (typeof $ !== 'undefined' && $(manageModalEl).is(':visible')))) {
        wasModalOpen = true;
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modalInst = bootstrap.Modal.getInstance(manageModalEl);
            if (modalInst) {
                modalInst.hide();
            } else {
                manageModalEl.classList.remove('show');
                manageModalEl.style.display = 'none';
            }
        } else if (typeof $ !== 'undefined') {
            $(manageModalEl).modal('hide');
        } else {
            manageModalEl.classList.remove('show');
            manageModalEl.style.display = 'none';
        }
    }

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Delete Payroll Project?',
            html: 'Are you sure you want to delete <strong>"' + projName + '"</strong>?<br><br>' +
                   '<span class="text-danger fw-semibold"><i class="fa-solid fa-triangle-exclamation me-1"></i> Warning:</span> ' +
                   'All saved workplace calculations, payslips, and attendance overrides linked to this project will be deleted permanently. ' +
                   '<br><small class="text-muted">(Meal Tracker records will remain safe and unlinked.)</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="fa-solid fa-trash-can me-1"></i> Yes, Delete Project',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                window.executeDeleteProject(projId);
            } else if (wasModalOpen && manageModalEl) {
                setTimeout(function() {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        var modalInst = bootstrap.Modal.getOrCreateInstance(manageModalEl);
                        modalInst.show();
                    } else if (typeof $ !== 'undefined') {
                        $(manageModalEl).modal('show');
                    } else {
                        manageModalEl.classList.add('show');
                        manageModalEl.style.display = 'block';
                    }
                }, 200);
            }
        });
    } else {
        if (confirm('Are you sure you want to delete "' + projName + '"?\n\nWarning: All saved workplace calculations, payslips, and attendance overrides linked to this project will be deleted permanently.')) {
            window.executeDeleteProject(projId);
        } else if (wasModalOpen && manageModalEl) {
            if (typeof $ !== 'undefined') $(manageModalEl).modal('show');
        }
    }
};

window.executeDeleteProject = function(projId) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Deleting...',
            text: 'Deleting project and clearing related records...',
            allowOutsideClick: false,
            didOpen: function() {
                Swal.showLoading();
            }
        });
    }

    var baseUrl = '<?= $GLOBALS['base_url'] ?? '' ?>';
    var postUrl = baseUrl ? (baseUrl + '/delete-payroll-project') : '/delete-payroll-project';

    var formData = new FormData();
    formData.append('project_id', projId);
    formData.append('is_ajax', '1');

    fetch(postUrl, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res && res.success) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: res.message || 'Payroll project deleted successfully.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(function() {
                    window.location.href = baseUrl + '/payroll-workspace';
                });
            } else {
                alert(res.message || 'Payroll project deleted successfully.');
                window.location.href = baseUrl + '/payroll-workspace';
            }
        } else {
            var errMsg = (res && res.message) || 'Failed to delete payroll project.';
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', errMsg, 'error');
            } else {
                alert('Error: ' + errMsg);
            }
        }
    })
    .catch(function(err) {
        console.error('Delete project error:', err);
        if (typeof Swal !== 'undefined') {
            Swal.fire('Error', 'Connection failed or server error occurred.', 'error');
        } else {
            alert('Connection failed or server error occurred.');
        }
    });
};
</script>


<!-- Filter Section -->
<div class="row mb-3">
    <div class="col-12">
        <div class="filters card__wrapper p-3 bg-white rounded shadow-sm">
            <form method="GET" action="<?= $GLOBALS['base_url'] ?>/payroll-workspace" class="row gx-3 gy-2 align-items-end" id="workspaceFilterForm">

                <!-- Hidden: carries filter_type on submit -->
                <input type="hidden" name="filter_type" id="filterTypeInput" value="<?= htmlspecialchars($_filterType) ?>">

                <!-- Filter Type Toggle -->
                <div class="col-sm-2">
                    <label class="form-label fw-semibold text-muted mb-1">Filter Type</label>
                    <select class="form-select" id="filterTypeSelect">
                        <option value="monthly" <?= $_filterType === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        <option value="custom"  <?= $_filterType === 'custom'  ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>

                <!-- Monthly: Year + Month -->
                <div class="col-sm-4" id="monthlyContainer" <?= $_filterType === 'custom' ? 'style="display:none;"' : '' ?>>
                    <div class="row gx-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold text-muted mb-1">Year</label>
                            <select class="form-select" name="year" id="year">
                                <?php
                                $startYear = 2020;
                                $endYear   = 2035;
                                for ($y = $startYear; $y <= $endYear; $y++): ?>
                                    <option value="<?= $y ?>" <?= $y == $_selectedYear ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold text-muted mb-1">Month</label>
                            <select class="form-select" name="month" id="month">
                                <?php
                                for ($m = 1; $m <= 12; $m++):
                                    $monthNum  = str_pad($m, 2, "0", STR_PAD_LEFT);
                                    $monthName = date('F', mktime(0, 0, 0, $m, 1)); ?>
                                    <option value="<?= $monthNum ?>" <?= $monthNum == str_pad($_selectedMonth, 2, "0", STR_PAD_LEFT) ? 'selected' : '' ?>><?= $monthName ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Custom Range: From Date + To Date -->
                <div class="col-sm-4" id="customContainer" <?= $_filterType !== 'custom' ? 'style="display:none;"' : '' ?>>
                    <div class="row gx-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold text-muted mb-1">From Date</label>
                            <input type="date" class="form-control" name="from_date" id="fromDate" value="<?= htmlspecialchars($_fromDate) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold text-muted mb-1">To Date</label>
                            <input type="date" class="form-control" name="to_date" id="toDate" value="<?= htmlspecialchars($_toDate) ?>">
                        </div>
                    </div>
                </div>

                <!-- Payroll Project -->
                <div class="col-xl-3 col-md-4">
                    <label class="form-label fw-semibold text-muted mb-1" for="payrollProject">Payroll Project</label>
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select w-100" name="payroll_project_id" id="payrollProject">
                            <option value="">Select Payroll Project</option>
                            <?php if (!empty($payroll_projects)): ?>
                                <?php foreach ($payroll_projects as $proj): ?>
                                    <option value="<?= htmlspecialchars($proj['id']) ?>"
                                        data-date="<?= htmlspecialchars($proj['date'] ?? '') ?>"
                                        data-iscustom="<?= htmlspecialchars($proj['is_custom_date'] ?? '0') ?>"
                                        data-start="<?= htmlspecialchars($proj['start_date'] ?? '') ?>"
                                        data-end="<?= htmlspecialchars($proj['end_date'] ?? '') ?>"
                                        <?= (!empty($_selectedProject) && (int)$_selectedProject == (int)$proj['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($proj['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="modal"
                            data-bs-target="#createProjectModal" style="height: 38px;">
                            <i class="fa-solid fa-plus me-1"></i> Create
                        </button>
                        <?php if ($isManagement): ?>
                            <button type="button" class="btn btn-outline-secondary text-nowrap" data-bs-toggle="modal"
                                data-bs-target="#manageProjectsModal" style="height: 38px;" title="Manage & Delete Payroll Projects">
                                <i class="fa-solid fa-folder-tree me-1"></i> Manage
                            </button>
                            <?php if (!empty($_selectedProject) && !empty($selectedProjectName)): ?>
                                <button type="button" class="btn btn-outline-danger text-nowrap btn-delete-payroll-proj"
                                    data-id="<?= (int)$_selectedProject ?>"
                                    data-name="<?= htmlspecialchars($selectedProjectName, ENT_QUOTES) ?>"
                                    onclick="confirmDeletePayrollProject(<?= (int)$_selectedProject ?>, '<?= htmlspecialchars(addslashes($selectedProjectName), ENT_QUOTES) ?>')"
                                    style="height: 38px;" title="Delete this Payroll Project">
                                    <i class="fa-solid fa-trash-can me-1"></i> Delete
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <input type="hidden" name="meal_project_id" id="mealProjectInput" value="<?= htmlspecialchars($selectedMealProjectId ?? '') ?>">

                <!-- Linked Meal Tracker (Auto-linked) -->
                <div class="col-xl-3 col-md-4">
                    <label class="form-label fw-semibold text-muted mb-1"><i class="fa-solid fa-utensils me-1 text-primary"></i> Linked Meal Tracker</label>
                    <div class="d-flex align-items-center" style="height: 38px;">
                        <?php if (!empty($activeMealProject)): ?>
                            <div class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 text-truncate w-100 fs-13 text-start d-flex align-items-center justify-content-between" title="Auto-linked Meal Project: <?= htmlspecialchars($activeMealProject['name']) ?> (<?= date('d M', strtotime($activeMealProject['from_date'])) ?> - <?= date('d M Y', strtotime($activeMealProject['to_date'])) ?>)">
                                <span class="text-truncate fw-semibold"><i class="fa-solid fa-circle-check me-1"></i> <?= htmlspecialchars($activeMealProject['name']) ?></span>
                                <span class="badge bg-success text-white ms-2" style="font-size: 10px;">Linked</span>
                            </div>
                        <?php else: ?>
                            <div class="badge bg-light text-muted border px-3 py-2 text-truncate w-100 fs-13 text-start d-flex align-items-center justify-content-between" title="No Meal Tracker matched for this cycle">
                                <span class="text-truncate"><i class="fa-solid fa-circle-info me-1"></i> No Meal Tracker Linked</span>
                                <span class="badge bg-secondary text-white ms-2" style="font-size: 10px;">None</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Search Button -->
                <div class="col-xl-2 col-md-auto">
                    <button type="submit" class="btn btn-primary" style="height:38px;">
                        <i class="fa-solid fa-search me-1"></i> Search
                    </button>
                    <a href="<?= $GLOBALS['base_url'] ?>/payroll-workspace" class="btn btn-outline-secondary ms-1" style="height:38px;">
                        <i class="fa-solid fa-rotate-left me-1"></i> Reset
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const filterTypeSelect  = document.getElementById('filterTypeSelect');
    const filterTypeInput   = document.getElementById('filterTypeInput');
    const monthlyContainer  = document.getElementById('monthlyContainer');
    const customContainer   = document.getElementById('customContainer');
    const yearSel           = document.getElementById('year');
    const monthSel          = document.getElementById('month');
    const fromDateInput     = document.getElementById('fromDate');
    const toDateInput       = document.getElementById('toDate');
    const projectSelect     = document.getElementById('payrollProject');

    function applyFilterType(type) {
        filterTypeInput.value = type;
        if (type === 'custom') {
            monthlyContainer.style.display = 'none';
            customContainer.style.display  = '';
            // disable monthly fields so they are not submitted
            if (yearSel)  yearSel.disabled  = true;
            if (monthSel) monthSel.disabled = true;
            // enable custom fields
            if (fromDateInput) fromDateInput.disabled = false;
            if (toDateInput)   toDateInput.disabled   = false;
        } else {
            monthlyContainer.style.display = '';
            customContainer.style.display  = 'none';
            if (yearSel)      yearSel.disabled      = false;
            if (monthSel)     monthSel.disabled      = false;
            if (fromDateInput) fromDateInput.disabled = true;
            if (toDateInput)   toDateInput.disabled   = true;
        }
    }

    // Init on load
    applyFilterType(filterTypeSelect.value);

    filterTypeSelect.addEventListener('change', function () {
        applyFilterType(this.value);
    });

    // Auto-sync Project Date (keep existing behaviour and handle custom projects)
    if (projectSelect) {
        projectSelect.addEventListener('change', function () {
            const selectedOpt = this.options[this.selectedIndex];
            if (selectedOpt && selectedOpt.value) {
                const isCustom = selectedOpt.getAttribute('data-iscustom') === '1';
                
                if (isCustom) {
                    filterTypeSelect.value = 'custom';
                    applyFilterType('custom');
                    const startDateVal = selectedOpt.getAttribute('data-start');
                    if (fromDateInput) fromDateInput.value = startDateVal;
                    if (toDateInput) toDateInput.value = selectedOpt.getAttribute('data-end');
                    if (startDateVal) {
                        const parts = startDateVal.split('-');
                        if (parts.length >= 2) {
                            if (yearSel)  yearSel.value  = parts[0];
                            if (monthSel) monthSel.value = parts[1];
                        }
                    }
                } else {
                    const dateVal = selectedOpt.getAttribute('data-date');
                    if (dateVal) {
                        filterTypeSelect.value = 'monthly';
                        applyFilterType('monthly');
                        const parts = dateVal.split('-');
                        if (parts.length >= 2) {
                            if (yearSel)  yearSel.value  = parts[0];
                            if (monthSel) monthSel.value = parts[1];
                        }
                    }
                }
            }
            setTimeout(() => document.getElementById('workspaceFilterForm').submit(), 50);
        });
    }
})();
</script>

<!-- Create Project Modal -->
<div class="modal fade" id="createProjectModal" tabindex="-1" aria-labelledby="createProjectModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= $GLOBALS['base_url'] ?>/payroll-project-store" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="createProjectModalLabel"><i
                            class="fa-solid fa-folder-plus me-2"></i>Create Payroll Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="form__group mb-3">
                        <label class="form-label" style="font-weight: 600;">Project Name <span
                                class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Enter project name" required>
                    </div>
                    <div class="form__group mb-3">
                        <label class="form-label" style="font-weight: 600;">Project Type <span
                                class="text-danger">*</span></label>
                        <select name="project_type" id="createProjectType" class="form-select" required>
                            <option value="monthly">Monthly</option>
                            <option value="custom">Custom Date Range</option>
                        </select>
                    </div>

                    <!-- Monthly -->
                    <div class="form__group mb-3" id="createMonthlyContainer">
                        <label class="form-label" style="font-weight: 600;">Project Month/Year <span
                                class="text-danger">*</span></label>
                        <input type="month" name="date" id="createProjectDate" class="form-control">
                    </div>

                    <!-- Custom Range -->
                    <div class="row mb-3" id="createCustomContainer" style="display: none;">
                        <div class="col-6">
                            <label class="form-label" style="font-weight: 600;">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="createProjectStart" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-weight: 600;">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="createProjectEnd" class="form-control">
                        </div>
                    </div>

                    <!-- Linked Meal Tracker Cycle Preview -->
                    <div class="p-3 mb-3 rounded border bg-light d-flex align-items-start gap-2" id="createMealCyclePreviewBox">
                        <i class="fa-solid fa-utensils text-success fs-5 mt-1"></i>
                        <div>
                            <div class="fw-bold text-dark small" id="createMealCycleTitle">Linked Meal Cycle: (Calculating...)</div>
                            <div class="text-muted" style="font-size: 12px;" id="createMealCycleDesc">
                                <i class="fa-solid fa-arrows-rotate text-primary me-1"></i> Matching Meal Project will be auto-linked or auto-created in Meal Tracker with live attendance synced.
                            </div>
                        </div>
                    </div>

                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            const pType = document.getElementById('createProjectType');
                            const mCont = document.getElementById('createMonthlyContainer');
                            const cCont = document.getElementById('createCustomContainer');
                            const pDate = document.getElementById('createProjectDate');
                            const pStart = document.getElementById('createProjectStart');
                            const pEnd = document.getElementById('createProjectEnd');
                            const mTitle = document.getElementById('createMealCycleTitle');
                            const mDesc = document.getElementById('createMealCycleDesc');

                            // Default to current year-month
                            if (!pDate.value) {
                                const now = new Date();
                                const y = now.getFullYear();
                                const m = String(now.getMonth() + 1).padStart(2, '0');
                                pDate.value = `${y}-${m}`;
                            }

                            function calculateCycleRange(year, month) {
                                let prevM = month - 1;
                                let prevY = year;
                                if (prevM === 0) {
                                    prevM = 12;
                                    prevY = year - 1;
                                }
                                const pad = (n) => String(n).padStart(2, '0');
                                return {
                                    start: `${prevY}-${pad(prevM)}-26`,
                                    end: `${year}-${pad(month)}-25`
                                };
                            }

                            function formatDateDisplay(dStr) {
                                if (!dStr) return '';
                                const d = new Date(dStr);
                                if (isNaN(d.getTime())) return dStr;
                                return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                            }

                            function updateMealCyclePreview() {
                                let start = '', end = '';
                                if (pType.value === 'monthly') {
                                    const val = pDate.value;
                                    if (val) {
                                        const parts = val.split('-');
                                        const cycle = calculateCycleRange(parseInt(parts[0], 10), parseInt(parts[1], 10));
                                        start = cycle.start;
                                        end = cycle.end;
                                    }
                                } else {
                                    start = pStart.value;
                                    end = pEnd.value;
                                }

                                if (start && end) {
                                    mTitle.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Linked Meal Cycle:</span> ' + formatDateDisplay(start) + ' to ' + formatDateDisplay(end);
                                    mDesc.innerHTML = 'Meal Tracker will auto-sync attendance records for <strong class="text-dark">' + start + '</strong> to <strong class="text-dark">' + end + '</strong>.';
                                } else {
                                    mTitle.innerHTML = '<span class="text-warning fw-semibold"><i class="fa-solid fa-calendar-day me-1"></i> Linked Meal Cycle:</span> Select from/to dates';
                                    mDesc.textContent = 'Meal Project will automatically match or be created for the selected date range.';
                                }
                            }

                            pType.addEventListener('change', function() {
                                if(this.value === 'monthly') {
                                    mCont.style.display = 'block';
                                    cCont.style.display = 'none';
                                    pDate.required = true;
                                    pStart.required = false;
                                    pEnd.required = false;
                                } else {
                                    mCont.style.display = 'none';
                                    cCont.style.display = 'flex';
                                    pDate.required = false;
                                    pStart.required = true;
                                    pEnd.required = true;

                                    // Pre-fill custom start and end from monthly cycle if empty
                                    if (!pStart.value || !pEnd.value) {
                                        const val = pDate.value || '<?= date("Y-m") ?>';
                                        const parts = val.split('-');
                                        const cycle = calculateCycleRange(parseInt(parts[0], 10), parseInt(parts[1], 10));
                                        if (!pStart.value) pStart.value = cycle.start;
                                        if (!pEnd.value) pEnd.value = cycle.end;
                                    }
                                }
                                updateMealCyclePreview();
                            });

                            pDate.addEventListener('change', updateMealCyclePreview);
                            pDate.addEventListener('input', updateMealCyclePreview);
                            pStart.addEventListener('change', updateMealCyclePreview);
                            pStart.addEventListener('input', updateMealCyclePreview);
                            pEnd.addEventListener('change', updateMealCyclePreview);
                            pEnd.addEventListener('input', updateMealCyclePreview);

                            // Initialize
                            pType.dispatchEvent(new Event('change'));
                        });
                    </script>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary border" style="background:#fff; color:#000;"
                        data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary"
                        style="background-color: #0f766e; border-color: #0f766e;">
                        <i class="fa-solid fa-save me-2"></i> Save Project
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($isManagement): ?>
<!-- Manage Payroll Projects Modal -->
<div class="modal fade" id="manageProjectsModal" tabindex="-1" aria-labelledby="manageProjectsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #0f766e, #115e59);">
                <h5 class="modal-title text-white" id="manageProjectsModalLabel">
                    <i class="fa-solid fa-folder-tree me-2"></i>Manage Payroll Projects
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="text-muted small mb-0">List of all payroll projects. Deleting a project will remove all its saved workspace calculations, payslips, and attendance overrides, while unlinking meal tracker records.</p>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Project Name</th>
                                <th>Type</th>
                                <th>Cycle Date Range</th>
                                <th class="text-center" style="width: 110px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($payroll_projects)): ?>
                                <?php $projSl = 1; foreach ($payroll_projects as $proj): ?>
                                    <?php 
                                    $isCust = !empty($proj['is_custom_date']);
                                    $dateRangeText = ($isCust && !empty($proj['start_date']) && !empty($proj['end_date']))
                                        ? date('d M Y', strtotime($proj['start_date'])) . ' - ' . date('d M Y', strtotime($proj['end_date']))
                                        : (!empty($proj['date']) ? date('F Y', strtotime($proj['date'] . '-01')) : 'N/A');
                                    ?>
                                    <tr>
                                        <td><?= $projSl++ ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($proj['name']) ?></strong>
                                            <?php if ((int)$proj['id'] === (int)$_selectedProject): ?>
                                                <span class="badge bg-primary-subtle text-primary ms-1">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isCust): ?>
                                                <span class="badge bg-info-subtle text-info">Custom Range</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary">Monthly</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted"><?= htmlspecialchars($dateRangeText) ?></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-payroll-proj"
                                                    data-id="<?= (int)$proj['id'] ?>"
                                                    data-name="<?= htmlspecialchars($proj['name'], ENT_QUOTES) ?>"
                                                    onclick="confirmDeletePayrollProject(<?= (int)$proj['id'] ?>, '<?= htmlspecialchars(addslashes($proj['name']), ENT_QUOTES) ?>')"
                                                    title="Delete this project">
                                                <i class="fa-solid fa-trash-can me-1"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">No payroll projects found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Workspace Table -->
<div class="row">
    <div class="col-xxl-12">

        <div class="card__wrapper border-0">
            <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                <h4 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Payroll Workplace
                </h4>
                <div>
                    <button type="button" class="btn btn-outline-primary fw-semibold shadow-sm me-2"
                        id="btnSyncTipsoiAttendance" onclick="syncTipsoiAttendance(this)" title="Fetch and sync live attendance from Tipsoi for unsynced employees">
                        <i class="fa-solid fa-arrows-rotate me-1"></i> Sync Tipsoi
                    </button>
                    <button type="button" class="btn btn-info fw-semibold shadow-sm me-2 text-white"
                        style="background-color: #0f766e; border-color: #0f766e;"
                        onclick="saveWorkspaceData(this)">
                        <i class="fa-solid fa-save me-1"></i> Save Data
                    </button>
                    <button type="button" class="btn btn-success fw-semibold shadow-sm me-2"
                        onclick="$('#workspaceTable').DataTable().button('.buttons-csv').trigger();">
                        <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                    </button>
                    <button type="button" class="btn btn-danger fw-semibold shadow-sm me-2" onclick="
                        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#workspaceTable')) {
                            $('#workspaceTable').DataTable().button('.buttons-pdf').trigger();
                        } else {
                            alert('Table is loading, please wait a moment.');
                        }
                    ">
                        <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
                    </button>
                </div>
            </div>
            


            <!-- Tipsoi Attendance Notice Banner (Non-blocking) -->
            <div class="alert alert-info d-flex align-items-center justify-content-between p-3 mb-3 border-0 shadow-sm d-none" id="tipsoiNoticeBanner" style="border-radius: 10px; background: #e0f2fe; color: #0369a1;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-info fs-5 text-info"></i>
                    <span><strong>Tipsoi Attendance:</strong> Some employees have not been synced yet from Tipsoi for this project cycle.</span>
                </div>
                <button type="button" class="btn btn-sm btn-primary fw-semibold" onclick="syncTipsoiAttendance(this)">
                    <i class="fa-solid fa-arrows-rotate me-1"></i> Sync Now
                </button>
            </div>

            <div class="table__wrapper" style="overflow: visible;">
                <table id="workspaceTable" class="table no-border-table mb-20">
                    <thead>
                        <tr class="table__title table__sort">
                            <th class="text-center" style="width: 40px;"><input type="checkbox" autocomplete="off" id="selectAllEmployees" class="form-check-input" checked style="cursor:pointer;" title="Select All"></th>
                            <th>SL</th>
                            <th>Department</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>ID</th>
                            <th>Workplace</th>
                            <th>Designation</th>
                            <th>Basic Salary</th>

                            <th>Recreation Allowance</th>
                            <th>Conveyance Allowance</th>
                            <th>Medical Allowance</th>
                            <th>House Rent Allowance</th>
                            <th>Gross</th>
                            <th>Daily Salary</th>
                            <th>Advance on Salary</th>
                            <th>Meal Count</th>
                            <th>Subsidized Lunch</th>
                            <th>Late/Unpaid/Absent (Days)</th>
                            <th>Late/Unpaid/Absent (Amount)</th>
                            <th>Salary Hold</th>
                            <th>Arrears</th>
                            <th>Others Deduction</th>

                            <th>Punctuality Bonus</th>
                            <th>Overtime</th>
                            <th>Arrear</th>
                            <th>Duty/Food/Car Allowance</th>
                            <th>Joining Date</th>
                            <th>Resignation Date</th>
                            <th>Actual Effective Days</th>
                            <th>Net Salary</th>
                            <th>Tax</th>
                            <th>Net Payable</th>
                            <th>Bank</th>
                            <th>Cash</th>
                            <th>Salary Remarks</th>
                            <th>Attendance Remarks</th>
                            <th>Account Name</th>
                            <th>Account No</th>
                            <th>Amount</th>
                            <th>ID Check</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $itemMap = [];
                        $itemPercents = [
                            'recreation' => 0.10,
                            'conveyance' => 0.10,
                            'medical' => 0.30,
                            'houserent' => 0.50
                        ];
                        foreach ($items as $x) {
                            $name = strtolower($x['name']);
                            if (strpos($name, 'advance') !== false)
                                $itemMap['advance'] = $x['id'];
                            elseif (strpos($name, 'lunch') !== false || strpos($name, 'meal') !== false)
                                $itemMap['lunch'] = $x['id'];
                            elseif (strpos($name, 'absent') !== false || strpos($name, 'lwp') !== false)
                                $itemMap['absent'] = $x['id'];
                            elseif (strpos($name, 'punctuality') !== false)
                                $itemMap['punctuality'] = $x['id'];
                            elseif (strpos($name, 'overtime') !== false)
                                $itemMap['overtime'] = $x['id'];
                            elseif (strpos($name, 'arrear') !== false && $x['type'] === 'earning')
                                $itemMap['arrear'] = $x['id'];
                            elseif (strpos($name, 'tax') !== false)
                                $itemMap['tax'] = $x['id'];
                            elseif (strpos($name, 'duty') !== false || strpos($name, 'food') !== false || strpos($name, 'car') !== false)
                                $itemMap['duty_allowance'] = $x['id'];
                            elseif (strpos($name, 'other') !== false)
                                $itemMap['other'] = $x['id'];

                            if (strpos($name, 'recreation') !== false) $itemPercents['recreation'] = (float)($x['percentage'] ?? 10) / 100;
                            if (strpos($name, 'conveyance') !== false) $itemPercents['conveyance'] = (float)($x['percentage'] ?? 10) / 100;
                            if (strpos($name, 'medical') !== false) $itemPercents['medical'] = (float)($x['percentage'] ?? 30) / 100;
                            if (strpos($name, 'house rent') !== false) $itemPercents['houserent'] = (float)($x['percentage'] ?? 50) / 100;
                        }

                        $sl = 1;
                        if (!empty($payslips)):
                            foreach ($payslips as $p):
                                $isExisting = !empty($p['id']);
                                $empIdFk = (int)($p['real_emp_id_fk'] ?? $p['id'] ?? 0);
                                $gross = (float) $p['emp_gross'];
                                $basic = (float) $p['emp_basic'];
                                $daily = $gross / 30;

                                // Calculate employee effective days within active payroll cycle
                                $cycleStartTs = strtotime($_fromDate);
                                $cycleEndTs   = strtotime($_toDate);
                                $cycleTotalDays = max(1, (int) round(($cycleEndTs - $cycleStartTs) / 86400) + 1);

                                $empJoinTs = !empty($p['joining_date']) ? strtotime($p['joining_date']) : $cycleStartTs;
                                $empResignTs = !empty($p['deleted_at']) ? strtotime(substr($p['deleted_at'], 0, 10)) : $cycleEndTs;

                                $effectiveStartTs = max($cycleStartTs, $empJoinTs);
                                $effectiveEndTs   = min($cycleEndTs, $empResignTs);

                                if ($effectiveStartTs > $effectiveEndTs) {
                                    $empEffectiveDays = 0;
                                } else {
                                    $empEffectiveDays = (int) round(($effectiveEndTs - $effectiveStartTs) / 86400) + 1;
                                    $empEffectiveDays = min($cycleTotalDays, max(0, $empEffectiveDays));
                                }

                                $nonWorkingCycleDays = max(0, $cycleTotalDays - $empEffectiveDays);

                                $effectiveGross = ($empEffectiveDays < $cycleTotalDays && $daily > 0)
                                    ? round($empEffectiveDays * $daily, 2)
                                    : $gross;

                                // Base salary allowances
                                $recAllow = ($isExisting && isset($p['recreation_allowance'])) ? (float)$p['recreation_allowance'] : round($basic * $itemPercents['recreation'], 2);
                                $convAllow = ($isExisting && isset($p['conveyance_allowance'])) ? (float)$p['conveyance_allowance'] : round($basic * $itemPercents['conveyance'], 2);
                                $medAllow = ($isExisting && isset($p['medical_allowance'])) ? (float)$p['medical_allowance'] : round($basic * $itemPercents['medical'], 2);
                                $hrAllow = ($isExisting && isset($p['house_rent_allowance'])) ? (float)$p['house_rent_allowance'] : round($basic * $itemPercents['houserent'], 2);

                                // Additional earnings
                                $punctBonus = $isExisting ? (float)($p['punctuality_bonus'] ?? 0) : 0;
                                $overtime = $isExisting ? (float)($p['overtime'] ?? 0) : 0;
                                $arrearsEarning = $isExisting ? (float)($p['arrears_earning'] ?? 0) : 0;
                                $dutyAllow = $isExisting ? (float)($p['duty_allowance'] ?? 0) : 0;

                                // Attendance deductions
                                $hasAttOverride = isset($p['att_late_unpaid_absent_days']) && $p['att_late_unpaid_absent_days'] !== null;

                                // Determine whether Attendance Report has newer saved data than Workplace draft
                                $attIsNewer = false;
                                if ($hasAttOverride && $isExisting) {
                                    $attTime = !empty($p['att_updated_at']) ? strtotime($p['att_updated_at']) : 0;
                                    $payslipTime = !empty($p['updated_at']) ? strtotime($p['updated_at']) : (!empty($p['created_at']) ? strtotime($p['created_at']) : 0);
                                    if ($attTime >= $payslipTime) {
                                        $attIsNewer = true;
                                    }
                                }

                                if ($isExisting && !$attIsNewer) {
                                    // Workplace has been saved and is authoritative
                                    $unpaidAbsentsDays = (float) ($p['saved_late_unpaid_absent_days'] ?? $p['late_unpaid_absent_days'] ?? 0);
                                    if ($unpaidAbsentsDays <= 0) {
                                        $unpaidAbsentsAmount = 0.00;
                                    } else {
                                        $unpaidAbsentsAmount = (isset($p['saved_unpaid_absent_amount']) && (float)$p['saved_unpaid_absent_amount'] > 0)
                                            ? (float)$p['saved_unpaid_absent_amount']
                                            : round($unpaidAbsentsDays * $daily, 2);
                                    }
                                } elseif ($hasAttOverride) {
                                    // Authoritative attendance from /payroll-attendance-report
                                    $unpaidAbsentsDays = (float) $p['att_late_unpaid_absent_days'];
                                    $unpaidAbsentsAmount = round($unpaidAbsentsDays * $daily, 2);
                                } else {
                                    // Tipsoi / fallback
                                    $unpaidAbsentsDays = isset($p['tipsoi_absent']) ? (float) $p['tipsoi_absent'] : 0;
                                    $unpaidAbsentsAmount = round($unpaidAbsentsDays * $daily, 2);
                                }

                                // Advance salary
                                $advanceSalary = $isExisting ? (float)($p['advance_salary'] ?? 0) : 0;

                                // Meal tracking and deduction
                                $isMealDeductible = isset($p['is_meal_deduction']) ? (int)$p['is_meal_deduction'] : 1;
                                $mealPct = isset($p['per_meal_percentage']) ? (float)$p['per_meal_percentage'] : 0.00;
                                $standardMealRate = isset($activeMealProject['standard_meal_amount']) && $activeMealProject['standard_meal_amount'] > 0 
                                    ? (float)$activeMealProject['standard_meal_amount'] 
                                    : 120.00;
                                $effectiveMealRate = max(0, round($standardMealRate * (1 - ($mealPct / 100)), 2));

                                $mealCount = 0;
                                if ($isExisting && isset($p['meal_count'])) {
                                    // Workplace has been saved/updated: manual edit is authoritative
                                    $mealCount = (float)$p['meal_count'];
                                } elseif (!empty($activeMealProject)) {
                                    // Initial sync from Meal Tracker (meal_records)
                                    $mealCount = isset($mealCountsByEmp[$empIdFk]) ? (float)$mealCountsByEmp[$empIdFk] : 0;
                                }

                                $subsidizeLunch = 0;
                                if ($isExisting && isset($p['subsidized_lunch'])) {
                                    // Workplace has been saved/updated: manual edit is authoritative
                                    $subsidizeLunch = (float)$p['subsidized_lunch'];
                                } elseif ($mealCount > 0) {
                                    $subsidizeLunch = round($mealCount * $effectiveMealRate, 2);
                                }

                                // Additional deductions
                                $arrearsDeduction = $isExisting ? (float)($p['arrears_deduction'] ?? 0) : 0;
                                $othersDeduction = $isExisting ? (float)($p['others_deduction'] ?? 0) : 0;
                                $tax = $isExisting ? (float)($p['tax'] ?? 0) : 0;

                                // Net salary calculation (prorated gross + additions - deductions)
                                $totalEarnings = $effectiveGross + $arrearsEarning + $dutyAllow + $punctBonus + $overtime;
                                $totalDeductions = $advanceSalary + $subsidizeLunch + $unpaidAbsentsAmount + $arrearsDeduction + $othersDeduction + $tax;
                                $netSalary = max(0, round($totalEarnings - $totalDeductions, 2));

                                // Bank / cash allocation
                                $hasBankDetails = !empty(trim($p['account_number'] ?? ''));
                                $paymentMethod = $hasBankDetails ? 'bank' : 'cash';
                                if ($hasBankDetails) {
                                    if ($isExisting && isset($p['bank_amount']) && (float)$p['bank_amount'] > 0 && isset($p['cash_amount']) && (float)$p['cash_amount'] > 0) {
                                        $bank = (float)$p['bank_amount'];
                                        $cash = max(0, round($netSalary - $bank, 2));
                                    } else {
                                        $bank = $netSalary;
                                        $cash = 0;
                                    }
                                } else {
                                    $bank = 0;
                                    $cash = $netSalary;
                                }
                                ?>
                                        <tr class="calc-row" data-payslip-id="<?= $p['id'] ?? 0 ?>" data-employee-id="<?= $p['real_emp_id_fk'] ?? $p['id'] ?? 0 ?>" data-employee-office-id="<?= htmlspecialchars($p['emp_id']) ?>" data-joining-date="<?= !empty($p['joining_date']) ? date('Y-m-d', strtotime($p['joining_date'])) : '' ?>" data-resignation-date="<?= !empty($p['deleted_at']) ? date('Y-m-d', strtotime($p['deleted_at'])) : '' ?>" data-effective-days="<?= $empEffectiveDays ?>" data-non-working-days="<?= $nonWorkingCycleDays ?>" data-has-db="<?= ($hasAttOverride || ($isExisting && !$attIsNewer) || isset($p['tipsoi_present'])) ? 'true' : 'false' ?>">
                                            <?php $excludedState = (isset($p['is_excluded']) && $p['is_excluded'] == 1) ? "" : "checked"; ?>
                                            <td class="text-center"><input type="checkbox" autocomplete="off" class="form-check-input emp-checkbox" <?= $excludedState ?> style="cursor:pointer;"></td>
                                            <td><?= $sl++ ?></td>
                                            <td><?= htmlspecialchars($p['department_name'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($p['full_name']) ?></td>
                                            <td><?= htmlspecialchars($p['email'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($p['emp_id']) ?></td>
                                            <td><?= htmlspecialchars($p['workplace'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($p['designation_name'] ?? 'N/A') ?></td>
                                            <td class="cell-basic" data-val="<?= $basic ?>"><?= number_format($basic, 2) ?></td>

                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-earning in-basic-allowance in-recreation" value="<?= $recAllow ?>" style="min-width: 80px;"></td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-earning in-basic-allowance in-conveyance" value="<?= $convAllow ?>" style="min-width: 80px;"></td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-earning in-basic-allowance in-medical" value="<?= $medAllow ?>" style="min-width: 80px;"></td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-earning in-basic-allowance in-houserent" value="<?= $hrAllow ?>" style="min-width: 80px;"></td>

                                            <td class="cell-gross fw-bold text-nowrap" data-val="<?= $gross ?>"><?= number_format($gross, 2) ?></td>
                                            <td class="cell-daily text-nowrap" data-val="<?= $daily ?>"><?= number_format($daily, 2) ?>
                                            </td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-deduct in-advance" value="<?= round($advanceSalary, 2) ?>" style="min-width: 80px;"></td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-mealcount" data-permeal="<?= $effectiveMealRate ?>" value="<?= $mealCount ?>" style="width: 60px;"></td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-deduct in-sublunch" value="<?= round($subsidizeLunch, 2) ?>" style="min-width: 80px;"></td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-absent-days" value="<?= $unpaidAbsentsDays ?>" style="width: 60px;"></td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-deduct in-unpaid" value="<?= round($unpaidAbsentsAmount, 2) ?>" style="min-width: 80px;"></td>
                                            <?php
                                            $isHold = strtolower(trim($p['salary_hold'] ?? '')) === 'hold';
                                            ?>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm shadow-sm btn-salary-hold <?= $isHold ? 'btn-danger' : 'btn-success' ?>" style="min-width: 80px;">
                                                    <?= $isHold ? 'Hold' : 'Unhold' ?>
                                                </button>
                                                <input type="hidden" class="in-salary-hold-string" value="<?= $isHold ? 'Hold' : '' ?>">
                                            </td>

                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-deduct in-arrears-deduct" value="<?= round($arrearsDeduction, 2) ?>" style="min-width: 80px;"></td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-deduct in-others-deduct" value="<?= round($othersDeduction, 2) ?>" style="min-width: 80px;"></td>

                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-earning in-punctuality" value="<?= round($punctBonus, 2) ?>" style="min-width: 80px;"></td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-earning in-overtime" value="<?= round($overtime, 2) ?>" style="min-width: 80px;"></td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-earning in-arrear" value="<?= round($arrearsEarning, 2) ?>" style="min-width: 80px;"></td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-earning in-otherallow" value="<?= round($dutyAllow, 2) ?>" style="min-width: 80px;"></td>
                                            <td><?= !empty($p['joining_date']) ? date('d-M-Y', strtotime($p['joining_date'])) : '&nbsp;' ?>
                                            </td>
                                            <td><?= !empty($p['deleted_at']) ? date('d-M-Y', strtotime($p['deleted_at'])) : '&nbsp;' ?>
                                            </td>
                                            <td class="cell-effective-days text-center" data-val="<?= $empEffectiveDays ?>"><?= $empEffectiveDays ?></td>
                                            <td class="cell-net fw-bold text-primary text-nowrap"
                                                data-payment-method="<?= $paymentMethod ?>">
                                                <?= number_format($netSalary, 2) ?>
                                            </td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-deduct in-tax" value="<?= round($tax, 2) ?>" style="min-width: 80px;"></td>
                                            <td class="cell-netpayable fw-bold text-success text-nowrap">
                                                <?= number_format($netSalary, 2) ?>
                                            </td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-bank" value="<?= round($bank, 2) ?>" style="min-width: 80px;"></td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-cash" value="<?= round($cash, 2) ?>" style="min-width: 80px;"></td>
                                            <td><input type="text" class="form-control form-control-sm in-remarks"
                                                    value="<?= htmlspecialchars($p['remarks'] ?? '') ?>" style="min-width: 120px;"></td>
                                            <td><input type="text" class="form-control form-control-sm in-att-remarks"
                                                    value="<?= htmlspecialchars($p['attendance_remarks'] ?? '') ?>" style="min-width: 120px;"></td>
                                            <td><?= htmlspecialchars($p['account_holder_name'] ?? '&nbsp;') ?></td>
                                            <td><?= htmlspecialchars($p['account_number'] ?? '&nbsp;') ?></td>
                                            <td class="cell-amount text-nowrap"><?= number_format($bank, 2) ?></td>
                                            <td>&nbsp;</td>
                                        </tr>
                                <?php endforeach; endif; ?>
                    </tbody>
                    <tfoot>
    <tr class="fw-bold workspace-footer-row" style="background-color: #e6f2f1; color: #0f766e;">
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td class="text-end ps-3">Totals</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Floating Scroll to Bottom Anchor -->
<a href="#payroll-bottom-section" 
   class="shadow rounded-circle d-flex align-items-center justify-content-center" 
   style="position: fixed; top: 120px; right: 30px; width: 60px; height: 60px; z-index: 1050; background-color: #fff; border: 3px solid #0f766e; color: #0f766e; text-decoration: none; font-size: 28px;" 
   title="Scroll down">
   <i class="fa-solid fa-arrow-down"></i>
</a>
<!-- Target ID at bottom -->
<div id="payroll-bottom-section"></div>


<script>
    document.addEventListener("DOMContentLoaded", function () {
        // --- Live Calculations ---

        // Generate Footer Headers dynamically to perfectly match the columns
        let footData = '';
        $('#workspaceTable thead th').each(function(i) {
            if (i === 7) {
                footData += '<td class="text-end pe-3">Totals:</td>';
            } else if (i < 8) {
                footData += '<td></td>';
            } else {
                let isIgnore = $(this).text().toLowerCase().includes('date') || $(this).text().toLowerCase().includes('remark') || $(this).text().toLowerCase().includes('account') || $(this).text().toLowerCase().includes('id check') || $(this).text().toLowerCase().includes('salary hold');
                footData += '<td class="text-end ' + (isIgnore ? 'tf-ignore' : '') + '">0.00</td>';
            }
        });
        $('#workspaceTable tfoot tr').html(footData);

        function attachCalculations() {

            document.querySelectorAll('.calc-row').forEach(row => {
                const inputs = row.querySelectorAll('input');

                function recalcRow(e) {
                    const basic = parseFloat(row.querySelector('.cell-basic').dataset.val) || 0;
                    const daily = parseFloat(row.querySelector('.cell-daily').dataset.val) || 0;
                    const perMeal = parseFloat(row.querySelector('.in-mealcount').dataset.permeal) || 0;

                    if (e && e.target.classList.contains('in-mealcount')) {
                        const mealCount = parseFloat(row.querySelector('.in-mealcount').value) || 0;
                        row.querySelector('.in-sublunch').value = (mealCount * perMeal).toFixed(2);
                    } else if (e && e.target.classList.contains('in-sublunch')) {
                        if (perMeal > 0) {
                            const subLunch = parseFloat(row.querySelector('.in-sublunch').value) || 0;
                            row.querySelector('.in-mealcount').value = (subLunch / perMeal).toFixed(2);
                        }
                    }

                    const absentInp = row.querySelector('.in-absent-days');
                    const unpaidInp = row.querySelector('.in-unpaid');

                    if (e && e.target.classList.contains('in-absent-days')) {
                        const absentDays = parseFloat(absentInp.value) || 0;
                        unpaidInp.value = (absentDays * daily).toFixed(2);
                    } else if (e && e.target.classList.contains('in-unpaid')) {
                        const unpaidAmt = parseFloat(unpaidInp.value) || 0;
                        if (unpaidAmt <= 0) {
                            absentInp.value = 0;
                            unpaidInp.value = "0.00";
                        } else if (daily > 0) {
                            absentInp.value = (unpaidAmt / daily).toFixed(2);
                        }
                    } else if (!e) {
                        const absentDays = parseFloat(absentInp.value) || 0;
                        if (absentDays <= 0) {
                            unpaidInp.value = "0.00";
                        }
                    }

                    // Calculate effective gross salary based on actual effective days
                    let gross = parseFloat(row.querySelector('.cell-gross').dataset.val) || 0;
                    const effDaysCell = row.querySelector('.cell-effective-days');
                    const effectiveDays = effDaysCell ? (parseFloat(effDaysCell.dataset.val || effDaysCell.textContent) || 30) : 30;
                    const totalCycleDays = <?= (int) ($cycleTotalDays ?? 30) ?>;

                    let effectiveGross = gross;
                    if (effectiveDays < totalCycleDays && daily > 0) {
                        effectiveGross = Math.round(effectiveDays * daily * 100) / 100;
                    }

                    let totalEarning = effectiveGross;
                    row.querySelectorAll('.in-earning').forEach(inp => {
                        if (!inp.classList.contains('in-basic-allowance')) {
                            totalEarning += (parseFloat(inp.value) || 0);
                        }
                    });
                    let totalDeduction = 0;
                    row.querySelectorAll('.in-deduct').forEach(inp => {
                        totalDeduction += (parseFloat(inp.value) || 0);
                    });

                    // Net Salary
                    let netSalary = totalEarning - totalDeduction;
                    row.querySelector('.cell-net').textContent = netSalary.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                    const tax = parseFloat(row.querySelector('.in-tax').value) || 0;
                    let netPayable = netSalary;

                    row.querySelector('.cell-netpayable').textContent = netPayable.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                    const payMethod = row.querySelector('.cell-net').dataset.paymentMethod || '';
                    let bankInput = row.querySelector('.in-bank');
                    let cashInput = row.querySelector('.in-cash');

                    if (e && e.target.classList.contains('in-bank')) {
                        cashInput.value = (netPayable - (parseFloat(bankInput.value) || 0)).toFixed(2);
                    } else if (e && e.target.classList.contains('in-cash')) {
                        bankInput.value = (netPayable - (parseFloat(cashInput.value) || 0)).toFixed(2);
                    } else {
                        if (payMethod.toLowerCase().includes('bank')) {
                            let cashVal = parseFloat(cashInput.value) || 0;
                            bankInput.value = (netPayable - cashVal).toFixed(2);
                        } else {
                            let bankVal = parseFloat(bankInput.value) || 0;
                            cashInput.value = (netPayable - bankVal).toFixed(2);
                        }
                    }

                    let dispBank = parseFloat(bankInput.value) || 0;
                    row.querySelector('.cell-amount').textContent = dispBank.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    if (e !== null) {
                        debouncedCalculateFooterTotals();
                    }
                }

                inputs.forEach(input => {
                    input.addEventListener('input', recalcRow);
                });

                // Initial calculation check on row (deferred footer totals)
                recalcRow(null);
            });

            // Single initial calculation of footer totals for all rows
            calculateFooterTotals();
        }

        let footerTotalsDebounceTimer = null;
        function debouncedCalculateFooterTotals() {
            clearTimeout(footerTotalsDebounceTimer);
            footerTotalsDebounceTimer = setTimeout(calculateFooterTotals, 60);
        }

        attachCalculations();
        
    
            // Dynamic Sticky Header Calculation
            setTimeout(function() {
                var nav = document.querySelector('.header') || document.querySelector('.navbar') || document.querySelector('header') || document.getElementById('header');
                var navHeight = nav ? nav.offsetHeight : 60;
                
                var style = document.createElement('style');
                style.innerHTML = `
                    .dataTables_scrollHead {
                        position: sticky !important;
                        top: ${navHeight}px !important;
                        z-index: 1020 !important;
                        background-color: #fff !important;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
                    }
                    .dataTables_scrollHead table {
                        background-color: #fff !important;
                    }
                    [bd-theme="bd-theme-dark"] .dataTables_scrollHead,
                    [bd-theme="bd-theme-dark"] .dataTables_scrollHead table,
                    [bd-theme="bd-theme-dark"] .dataTables_scrollHeadInner {
                        background-color: #1e293b !important;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.4) !important;
                    }
                `;
                document.head.appendChild(style);
                
                // Force all wrappers to allow sticky inheritance
                var parent = document.querySelector('.dataTables_scrollHead');
                while(parent && parent.tagName !== 'BODY') {
                    if (parent.classList.contains('card-body') || parent.classList.contains('card') || parent.classList.contains('table__wrapper')) {
                        parent.style.overflow = 'visible';
                    }
                    parent = parent.parentElement;
                }
            }, 300);

    function calculateFooterTotals() {
        var nodes = [];
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#workspaceTable')) {
            nodes = $('#workspaceTable').DataTable().rows().nodes();
        } else {
            nodes = document.querySelectorAll('.calc-row');
        }
        
        let colCount = $('#workspaceTable thead th').length;
        let totals = new Array(colCount).fill(0);
        
        $(nodes).each(function() {
            var row = $(this);
            if (!row.find('.emp-checkbox').is(':checked')) return;

            row.find('td').each(function(index) {
                if (index < 7) return; // Skip strings
                
                let val = 0;
                let input = $(this).find('input[type="number"]');
                if (input.length > 0) {
                    val = parseFloat(input.val()) || 0;
                } else if ($(this).hasClass('text-center') || $(this).find('button').length > 0 || $(this).text().includes('-') && $(this).text().trim().length > 6) {
                    // Skip dates and buttons (Salary Hold, Joining Date, Resignation Date)
                    val = 0;
                } else {
                    val = parseFloat($(this).text().replace(/,/g, '')) || 0;
                }
                if (!isNaN(val)) {
                    totals[index] += val;
                }
            });
        });

        $('#workspaceTable tfoot').show();
        $('.dataTables_scrollFoot tfoot tr, #workspaceTable tfoot tr').each(function() {
            $(this).find('td').each(function(index) {
            if (index > 7) {
                // Formatting
                let valFormat = totals[index].toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                if (totals[index] === 0 && $('#workspaceTable tbody tr:first td').eq(index).find('input, button, .text-center').length === 0 && $('#workspaceTable tbody tr:first td').eq(index).text().trim() === '') {
                    // Skip empty columns like Remarks
                    $(this).text('');
                } else if ($(this).hasClass('tf-ignore')) {
                    $(this).text('');
                } else {
                    $(this).text(valFormat);
                }
            }
        });
        });
    }


// Removed redundant initial dispatchEvent loop for 10x faster rendering

        // --- Date range from PHP (works for both Monthly and Custom Range modes) ---
        const fromDateStr = "<?= $selectedFromDate ?? date('Y-m-01') ?>";
        const toDateStr   = "<?= $selectedToDate   ?? date('Y-m-t')  ?>";

        // Calculate effective days per employee in the range and update "Actual Effective Days" cells
        (function () {
            const cycleFrom = new Date(fromDateStr);
            const cycleTo   = new Date(toDateStr);
            const totalCycleDays = Math.round((cycleTo - cycleFrom) / (1000 * 60 * 60 * 24)) + 1;

            document.querySelectorAll('#workspaceTable tbody tr.calc-row').forEach(row => {
                const cell = row.querySelector('.cell-effective-days');
                if (!cell) return;

                const joinStr = row.getAttribute('data-joining-date') || '';
                const resignStr = row.getAttribute('data-resignation-date') || '';

                let empStart = cycleFrom;
                if (joinStr) {
                    const jDate = new Date(joinStr);
                    if (!isNaN(jDate) && jDate > cycleFrom) {
                        empStart = jDate;
                    }
                }

                let empEnd = cycleTo;
                if (resignStr) {
                    const rDate = new Date(resignStr);
                    if (!isNaN(rDate) && rDate < cycleTo) {
                        empEnd = rDate;
                    }
                }

                let effDays = 0;
                if (empStart <= empEnd) {
                    effDays = Math.round((empEnd - empStart) / (1000 * 60 * 60 * 24)) + 1;
                    effDays = Math.max(0, Math.min(totalCycleDays, effDays));
                }

                cell.textContent = effDays;
                cell.setAttribute('data-val', effDays);
            });
        })();

        // --- Tipsoi Attendance Sync (Fast & On-Demand, no 8.6MB auto-freeze on load) ---
        window.syncTipsoiAttendance = function(btn) {
            var $btn = $(btn);
            var origHtml = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Syncing...');

            var params = new URLSearchParams({
                from_date: fromDateStr,
                to_date:   toDateStr
            });
            var projSelect = document.getElementById('payrollProject');
            if (projSelect && projSelect.value) {
                params.set('payroll_project_id', projSelect.value);
            }

            var overridePromise = (projSelect && projSelect.value) ? fetch(`<?= $GLOBALS['base_url'] ?>/api/payroll/attendance-overrides?project_id=` + encodeURIComponent(projSelect.value)).then(r => r.json()).catch(() => ({ success: false, overrides: {} })) : Promise.resolve({ success: false, overrides: {} });
            Promise.all([fetch(`<?= $GLOBALS['base_url'] ?>/api/hrm/attendance?` + params.toString()).then(r => r.json()), overridePromise])
            .then(([res, overrideRes]) => {
                var dbOverrides = (overrideRes && overrideRes.success && overrideRes.overrides) ? overrideRes.overrides : {};
                $btn.prop('disabled', false).html(origHtml);
                if (res.success && res.data) {
                    var rawData = res.data;
                    var localEmployees = res.local_employees || [];
                    var updateCount = 0;

                    var allRows = Array.from(document.querySelectorAll('.calc-row'));
                    var empRowMap = new Map();
                    allRows.forEach(r => {
                        var eid = r.getAttribute('data-employee-id');
                        if (eid) empRowMap.set(String(eid), r);
                    });

                    rawData.forEach(emp => {
                        var attMap = emp.attendance || {};
                        var summ = emp.summary || {};
                        var officeId = String(emp.employeeOfficeId || emp.employeeId || '').toLowerCase().trim();
                        var nameNormalized = String(emp.employeeName || '').toLowerCase().trim();

                        var localMatch = localEmployees.find(le => {
                            var leId = String(le.employee_id || '').toLowerCase().trim();
                            var leName = String(le.full_name || '').toLowerCase().trim();
                            return (officeId && leId === officeId) || (nameNormalized && leName === nameNormalized);
                        });

                        if (localMatch) {
                            var matchedRow = empRowMap.get(String(localMatch.id));
                            if (matchedRow) {
                                var present = 0, absent = 0;
                                if (summ.totalAbsent !== undefined) {
                                    absent = parseInt(summ.totalAbsent, 10) || 0;
                                    present = parseInt(summ.totalPresent, 10) || 0;
                                } else {
                                    Object.keys(attMap).forEach(ts => {
                                        var dayAtt = attMap[ts];
                                        if (dayAtt.isPresent) present++;
                                        else if (!dayAtt.isOffday && !dayAtt.isHoliday && !dayAtt.isLeave) absent++;
                                    });
                                }

                                var finalLate = parseInt(summ.totalLateEntry, 10) || 0;
                                var lateDeduction = Math.floor(finalLate / 3);
                                var empOverride = dbOverrides[localMatch.id] || dbOverrides[String(localMatch.id)] || null;
                                var totalAbsentDays = (empOverride && empOverride.late_unpaid_absent_days !== undefined && empOverride.late_unpaid_absent_days !== null) ? parseFloat(parseFloat(empOverride.late_unpaid_absent_days).toFixed(2)) : parseFloat((absent + lateDeduction).toFixed(2));

                                var absentInput = matchedRow.querySelector('.in-absent-days');
                                var unpaidInp = matchedRow.querySelector('.in-deduct.in-unpaid');
                                var dailyVal = parseFloat(matchedRow.querySelector('.cell-daily').dataset.val) || 0;

                                if (absentInput) {
                                    absentInput.value = totalAbsentDays;
                                    if (unpaidInp) {
                                        unpaidInp.value = (totalAbsentDays * dailyVal).toFixed(2);
                                    }
                                    absentInput.dispatchEvent(new Event('input', { bubbles: true }));
                                    matchedRow.setAttribute('data-has-db', 'true');
                                    updateCount++;
                                }
                            }
                        }
                    });

                    Object.keys(dbOverrides).forEach(eid => { var row = empRowMap.get(String(eid)); var ov = dbOverrides[eid]; if (row && ov && ov.late_unpaid_absent_days !== undefined && ov.late_unpaid_absent_days !== null) { var absInp = row.querySelector('.in-absent-days'); var unpInp = row.querySelector('.in-deduct.in-unpaid'); var dVal = parseFloat(row.querySelector('.cell-daily').dataset.val) || 0; var tDays = parseFloat(parseFloat(ov.late_unpaid_absent_days).toFixed(2)); if (absInp && parseFloat(absInp.value) !== tDays) { absInp.value = tDays; if (unpInp) unpInp.value = (tDays * dVal).toFixed(2); absInp.dispatchEvent(new Event('input', { bubbles: true })); row.setAttribute('data-has-db', 'true'); updateCount++; } } });
                    calculateFooterTotals();
                    $('#tipsoiNoticeBanner').slideUp();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Synced attendance for ' + updateCount + ' employees from Tipsoi!',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                } else {
                    alert(res.message || 'Failed to sync Tipsoi attendance.');
                }
            })
            .catch(err => {
                $btn.prop('disabled', false).html(origHtml);
                alert('Tipsoi sync error: ' + err.message);
            });
        };

        // Check if attendance is missing and surface banner if needed (non-blocking)
        var allRows = Array.from(document.querySelectorAll('.calc-row'));
        var missingCount = allRows.filter(r => r.getAttribute('data-has-db') === 'false').length;
        if (missingCount > 0) {
            $('#tipsoiNoticeBanner').removeClass('d-none');
        }

        // Dynamically rename 'Save Data' button if data is already saved
        const hasSavedData = Array.from(document.querySelectorAll('.calc-row')).some(row => {
            const pid = parseInt(row.getAttribute('data-payslip-id') || '0', 10);
            return pid > 0;
        });

        const saveBtn = document.querySelector('button[onclick="saveWorkspaceData(this)"]');
        if (saveBtn) {
            if (hasSavedData) {
                saveBtn.innerHTML = '<i class="fa-solid fa-edit me-1"></i> Update Data';
            } else {
                saveBtn.innerHTML = '<i class="fa-solid fa-save me-1"></i> Save Data';
            }
        }

        // --- DataTables with CSV and PDF ---
        if (typeof $ !== 'undefined' && $.fn.DataTable) {
            var table = $('#workspaceTable').DataTable({
                deferRender: true,
                autoWidth: false,
                responsive: false,
                scrollX: true,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                pageLength: 25,
                
                language: {
                    search: "",
                    searchPlaceholder: "Search...",
                    lengthMenu: "_MENU_ entries"
                },
                dom: '<"row align-items-center mb-3"<"col-sm-6 text-start"l><"col-sm-6 text-end"f><"d-none"B>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row align-items-center mt-3"<"col-sm-6 text-start"i><"col-sm-6 text-end"p>>',
                buttons: [
                    {
                        extend: 'csvHtml5',
                        text: 'CSV',
                        className: 'buttons-csv',
                        title: 'Payroll_Workplace_Export',
                          footer: true,
                          exportOptions: {
                            rows: function ( idx, data, node ) {
                                return $(node).find('.emp-checkbox').is(':checked');
                            },
                            columns: ':not(:first-child)',
                            format: {
                                  body: function (inner, rowidx, colidx, node) {
                                      if ($(node).find('input').length > 0) {
                                          return $(node).find('input').val();
                                      } else {
                                          return inner.replace(/(<([^>]+)>)/ig, "").trim();
                                      }
                                  },
                                  footer: function (inner, colidx, node) {
                                      return inner ? inner.toString().replace(/(<([^>]+)>)/ig, "").trim() : "";
                                  }
                              }
                        }
                    },
                    {
                        text: '<i class="fa-solid fa-file-pdf me-1"></i> PDF',
                        className: 'btn btn-danger btn-sm buttons-pdf',
                        action: function (e, dt, node, config) {
                            exportDynamicPDF(table);
                        }
                    }
                ]
            });

            // Extract all rows from DataTable and POST to PDF endpoint
            function exportDynamicPDF(dt) {
                var yearSel = document.getElementById('year');
                var monthSel = document.getElementById('month');
                var year = yearSel ? yearSel.value : '';
                var month = monthSel ? monthSel.value : '';
                var projectSelect = document.getElementById('payrollProject');
                var projectId = projectSelect ? projectSelect.value : '';
                var projectName = '';
                if (projectSelect && projectSelect.selectedIndex >= 0) {
                    var pText = projectSelect.options[projectSelect.selectedIndex].text.trim();
                    if (!pText.toLowerCase().includes('select')) {
                        projectName = pText;
                    }
                    if (!year) {
                        var pDate = projectSelect.options[projectSelect.selectedIndex].getAttribute('data-date');
                        if (pDate) {
                            var dp = pDate.split('-');
                            if (dp.length >= 2) {
                                year = dp[0];
                                month = dp[1];
                            }
                        }
                    }
                }

                var rowData = [];
                var headerData = [];
                var footerData = [];

                // 1. Get header data (skip column 0 checkbox)
                $(dt.table().header()).find('th').each(function (idx) {
                    if (idx === 0) return;
                    headerData.push($(this).text().trim());
                });

                // 2. Get data from all pages
                var dtNodes = dt.rows().nodes();
                var slCounter = 1;

                $(dtNodes).each(function () {
                    var tr = $(this);
                    // Filter out unchecked employees
                    if (tr.find('.emp-checkbox').length > 0 && !tr.find('.emp-checkbox').is(':checked')) {
                        return;
                    }

                    var rowArr = [];
                    tr.find('td').each(function (index) {
                        if (index === 0) return; // Skip checkbox column
                        var cell = $(this);
                        if (index === 1) {
                            rowArr.push(slCounter++);
                            return;
                        }
                        if (cell.find('input[type="hidden"].in-salary-hold-string').length > 0) {
                            rowArr.push(cell.find('input[type="hidden"].in-salary-hold-string').val() || 'Unhold');
                        } else if (cell.find('input:not([type="hidden"])').length > 0) {
                            rowArr.push(cell.find('input:not([type="hidden"])').val());
                        } else {
                            rowArr.push(cell.text().trim());
                        }
                    });
                    rowData.push(rowArr);
                });

                // 3. Get footer totals if available
                var $footTds = $('.dataTables_scrollFoot tfoot tr td');
                if ($footTds.length === 0) {
                    $footTds = $('#workspaceTable tfoot tr td');
                }
                if ($footTds.length > 0) {
                    $footTds.each(function (idx) {
                        if (idx === 0) return; // Skip checkbox column
                        footerData.push($(this).text().trim());
                    });
                }

                var form = document.createElement("form");
                form.method = "POST";
                form.action = '<?= $GLOBALS['base_url'] ?? '/' ?>/payroll-workspace-pdf?year=' + encodeURIComponent(year) + '&month=' + encodeURIComponent(month);
                form.target = "_blank"; // Open PDF in new tab

                function addHidden(name, val) {
                    var inp = document.createElement("input");
                    inp.type = "hidden";
                    inp.name = name;
                    inp.value = val;
                    form.appendChild(inp);
                }

                addHidden("table_data", JSON.stringify(rowData));
                addHidden("table_headers", JSON.stringify(headerData));
                addHidden("table_footers", JSON.stringify(footerData));
                addHidden("project_name", projectName);
                addHidden("payroll_project_id", projectId);

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            }
            
            // Assign save function to window so the button can access it
            window.saveWorkspaceData = function(btn) {
                var projectId = $('#payrollProject').val();
                if (!projectId) {
                    Swal.fire('Error', 'Please select a Payroll Project before saving.', 'error');
                    return;
                }

                $(btn).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

                var rowData = [];
                var dtNodes = table.rows().nodes();

                $(dtNodes).each(function () {
                    var tr = $(this);
                    
                    var isExcluded = tr.find('.emp-checkbox').is(':checked') ? 0 : 1;
                    var payslipIdStr = tr.attr('data-payslip-id');
                    var payslipId = parseInt(payslipIdStr || '0', 10);
                    var employeeId = parseInt(tr.attr('data-employee-id') || '0', 10);
                    console.log('Sending row:', { payslipIdStr: payslipIdStr, parsedPayslipId: payslipId, employeeId: employeeId });
                    
                    var netSalary = parseFloat(tr.find('.cell-net').text().trim().replace(/,/g, '')) || 0;
                    var totalEarningBase = parseFloat(tr.find('.cell-gross').attr('data-val')) || 0;
                    var totalEarning = totalEarningBase;
                    var totalDeduction = 0;
                    var salaryHoldString = tr.find('.in-salary-hold-string').val() || '';
                    var bankAmount = parseFloat(tr.find('.in-bank').val()) || 0;
                    var cashAmount = parseFloat(tr.find('.in-cash').val()) || 0;
                    var lateUnpaidAbsentDays = parseFloat(tr.find('.in-absent-days').val()) || 0;
                    var remarks = tr.find('.in-remarks').val() || '';
                    var attRemarks = tr.find('.in-att-remarks').val() || '';

                    // New fields
                    var recreation_allowance = parseFloat(tr.find('.in-recreation').val()) || 0;
                    var conveyance_allowance = parseFloat(tr.find('.in-conveyance').val()) || 0;
                    var medical_allowance = parseFloat(tr.find('.in-medical').val()) || 0;
                    var house_rent_allowance = parseFloat(tr.find('.in-houserent').val()) || 0;
                    var advance_salary = parseFloat(tr.find('.in-advance').val()) || 0;
                    var meal_count = parseFloat(tr.find('.in-mealcount').val()) || 0;
                    var subsidized_lunch = parseFloat(tr.find('.in-sublunch').val()) || 0;
                    var unpaid_absent_amount = parseFloat(tr.find('.in-unpaid').val()) || 0;
                    var arrears_deduction = parseFloat(tr.find('.in-arrears-deduct').val()) || 0;
                    var others_deduction = parseFloat(tr.find('.in-others-deduct').val()) || 0;
                    var punctuality_bonus = parseFloat(tr.find('.in-punctuality').val()) || 0;
                    var overtime = parseFloat(tr.find('.in-overtime').val()) || 0;
                    var arrears_earning = parseFloat(tr.find('.in-arrear').val()) || 0;
                    var duty_allowance = parseFloat(tr.find('.in-otherallow').val()) || 0;
                    var tax = parseFloat(tr.find('.in-tax').val()) || 0;
                    
                    var dynamicDetails = {}; // Unused now but kept for compatibility
                    
                    tr.find('.in-earning, .in-deduct').each(function() {
                        var val = parseFloat($(this).val()) || 0;
                        if ($(this).hasClass('in-deduct')) {
                            totalDeduction += val;
                        }
                        if ($(this).hasClass('in-earning') && !$(this).hasClass('in-basic-allowance')) {
                            totalEarning += val;
                        }
                    });

                    rowData.push({
                        payslip_id: payslipId,
                        employee_id: employeeId,
                        net_salary: netSalary,
                        total_earning: totalEarning,
                        total_deduction: totalDeduction,
                        salary_hold: salaryHoldString,
                        bank_amount: bankAmount,
                        cash_amount: cashAmount,
                        late_unpaid_absent_days: lateUnpaidAbsentDays,
                        remarks: remarks,
                        attendance_remarks: attRemarks,
                        is_excluded: isExcluded,
                        details: dynamicDetails,
                        recreation_allowance: recreation_allowance,
                        conveyance_allowance: conveyance_allowance,
                        medical_allowance: medical_allowance,
                        house_rent_allowance: house_rent_allowance,
                        advance_salary: advance_salary,
                        meal_count: meal_count,
                        subsidized_lunch: subsidized_lunch,
                        unpaid_absent_amount: unpaid_absent_amount,
                        arrears_deduction: arrears_deduction,
                        others_deduction: others_deduction,
                        punctuality_bonus: punctuality_bonus,
                        overtime: overtime,
                        arrears_earning: arrears_earning,
                        duty_allowance: duty_allowance,
                        tax: tax
                    });
                });

                if (rowData.length === 0) {
                    Swal.fire('Warning', 'No payslips data to save.', 'warning');
                    $(btn).prop('disabled', false).html(hasSavedData ? '<i class="fa-solid fa-edit me-1"></i> Update Data' : '<i class="fa-solid fa-save me-1"></i> Save Data');
                    return;
                }

                $.ajax({
                    url: '<?= $GLOBALS['base_url'] ?>/save-workspace-payslips',
                    type: 'POST',
                    contentType: 'application/json',
                    dataType: 'json',
                    data: JSON.stringify({
                        project_id: projectId,
                        year: $('select[name="year"]').val() || '',
                        month: $('select[name="month"]').val() || '',
                        rows: rowData
                    }),
                    success: function (res) {
                        try {
                            if (typeof res === 'string') {
                                res = JSON.parse(res);
                            }
                        } catch(e) {}
                        
                        if (res.success) {
                            Swal.fire('Success', res.message, 'success').then(() => {
                                window.location.href = '<?= $GLOBALS['base_url'] ?>/payroll-workspace?payroll_project_id=' + projectId;
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Operation failed', 'error');
                            $(btn).prop('disabled', false).html(hasSavedData ? '<i class="fa-solid fa-edit me-1"></i> Update Data' : '<i class="fa-solid fa-save me-1"></i> Save Data');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Server connection failed', 'error');
                        $(btn).prop('disabled', false).html(hasSavedData ? '<i class="fa-solid fa-edit me-1"></i> Update Data' : '<i class="fa-solid fa-save me-1"></i> Save Data');
                    }
                });
            };
        }

        // Clean event listeners wrapped inside DOMContentLoaded (safe with footer jQuery)
        $(document).on('click', '.btn-salary-hold', function() {
            var btn = $(this);
            var hiddenInput = btn.siblings('.in-salary-hold-string');
            if (hiddenInput.val() === 'Hold') {
                hiddenInput.val('');
                btn.text('Unhold').removeClass('btn-danger').addClass('btn-success');
            } else {
                hiddenInput.val('Hold');
                btn.text('Hold').removeClass('btn-success').addClass('btn-danger');
            }
        });

        // --- Database-driven Employee Selection ---
        function updateMasterCheckbox() {
            let allChecked = true;
            let totalCount = 0;
            var nodes = [];
            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#workspaceTable')) {
                nodes = $('#workspaceTable').DataTable().rows().nodes();
            } else {
                nodes = document.querySelectorAll('.calc-row');
            }
            $(nodes).each(function() {
                if ($(this).find('.emp-checkbox').length > 0) {
                    totalCount++;
                    if (!$(this).find('.emp-checkbox').is(':checked')) {
                        allChecked = false;
                    }
                }
            });
            $('#selectAllEmployees').prop('checked', allChecked && totalCount > 0);
        }
        
        setTimeout(function() {
            updateMasterCheckbox();
            calculateFooterTotals();
        }, 200);

        if ($.fn.DataTable) {
            $('#workspaceTable').on('draw.dt', function() {
                updateMasterCheckbox();
                calculateFooterTotals();
            });
        }

        $(document).on('change', '#selectAllEmployees', function() {
            var isChecked = $(this).is(':checked');
            var nodes = [];
            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#workspaceTable')) {
                nodes = $('#workspaceTable').DataTable().rows().nodes();
            } else {
                nodes = document.querySelectorAll('.calc-row');
            }
            $(nodes).each(function() {
                $(this).find('.emp-checkbox').prop('checked', isChecked);
            });
            calculateFooterTotals();
        });
        
        $(document).on('change', '.emp-checkbox', function() {
            if (!$(this).is(':checked')) {
                $('#selectAllEmployees').prop('checked', false);
            } else {
                updateMasterCheckbox();
            }
            calculateFooterTotals();
        });

        // Delegated click listener for delete buttons
        $(document).on('click', '.btn-delete-payroll-proj', function(e) {
            e.preventDefault();
            var id = $(this).attr('data-id') || $(this).data('id');
            var name = $(this).attr('data-name') || $(this).data('name');
            window.confirmDeletePayrollProject(id, name);
        });

    }); // End of DOMContentLoaded
</script>

