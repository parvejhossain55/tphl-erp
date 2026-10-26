<?php
$page_title = "Holiday Page";
$page = 'Holidays ';
$show_breadcrumb = true;

?>
<div class="breadcrumb__btn my-3">
  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNewHolidays">Add Holidays</button>
</div>



<!-- CONTENT -->
<div class="content">

  <!-- STAT CARDS -->
  <div class="row mb-4">
    <div class="col-6 col-xl-3">
      <div class="stat-card card__wrapper">
        <div class="card__icon"><i class="fas fa-flag" style="color:var(--accent)"></i></div>
        <div class="value" id="cardTotalHolidays"><?= $totalYearHoliday ?></div>
        <div class="label">Total Holidays (<?= date('Y') ?>)</div>

      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card card__wrapper">
        <div class="card__icon"><i class="fas fa-calendar-check" style="color:#3b82f6"></i></div>
        <div class="value" id="cardThisMonthHolidays"><?= $thisMonthHoliday ?></div>
        <div class="label">This Month Holidays</div>

      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card card__wrapper">
        <div class="card__icon"><i class="fas fa-sun" style="color:#10b981"></i></div>
        <div class="value" id="cardWeeklyOff"><?= $weeklyHolidayCount ?></div>
        <div class="label">Weekly Off</div>
        <div class="change up"><i class="fas fa-check me-1"></i>Total Year</div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card card__wrapper">
        <div class="card__icon"><i class="fas fa-mosque" style="color:#f59e0b"></i></div>
        <div class="value" id="cardUpcomingHolidays"><?= $upcomingHolidayCount ?></div>
        <div class="label">Upcoming Holidays</div>
        <div class="change up"><i class="fas fa-clock me-1"></i>Next 30 Days</div>
      </div>
    </div>
  </div>


  <!-- Nav tabs -->
  <ul class="nav nav-tabs mb-4" id="holidayTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="local-holidays-tab" data-bs-toggle="tab" data-bs-target="#local-holidays" type="button" role="tab" aria-controls="local-holidays" aria-selected="true">
        <i class="fas fa-database me-2"></i>Local ERP Holidays
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tipsoi-holidays-tab" data-bs-toggle="tab" data-bs-target="#tipsoi-holidays" type="button" role="tab" aria-controls="tipsoi-holidays" aria-selected="false">
        <i class="fas fa-cloud-download-alt me-2"></i>Tipsoi Holidays
      </button>
    </li>
  </ul>

  <!-- Tab panes -->
  <div class="tab-content" id="holidayTabsContent">
    
    <!-- Tab 1: Local ERP Holidays -->
    <div class="tab-pane fade show active" id="local-holidays" role="tabpanel" aria-labelledby="local-holidays-tab">
      <div class="row">
        <div class="card__wrapper">
          <div class="row">
            <div class="col-xxl-12">
              <div class="table__wrapper table-responsive">
                <table class="table mb-20" id="dataTableDefualt">
                  <thead>
                    <tr class="table__title">
                      <th>Date</th>
                      <th>Day</th>
                      <th>Holiday Name</th>
                      <th>Applies To</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody class="table__body">
                    <?php foreach ($holidays as $h):
                      $start = strtotime($h['start_date']);
                      $end   = strtotime($h['end_date']);
                      $title = htmlspecialchars($h['title']);
                      $appliesTo = $h['applies_to'] ?? 'all';

                      for ($d = $start; $d <= $end; $d += 86400):
                        $dateStr = date('Y-m-d', $d);
                        $dayName = date('l', $d);
                        $dateDisplay = date('j F Y', $d);
                    ?>
                        <tr>
                          <td class="table__date"><?= $dateDisplay ?></td>
                          <td class="table__day"><?= $dayName ?></td>
                          <td class="table__name fw-bold"><?= $title ?></td>
                          <td>
                            <?php if ($appliesTo === 'department'): ?>
                              <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                <i class="fa-solid fa-building me-1"></i> Dept: <?= htmlspecialchars($h['department_name'] ?? 'N/A') ?>
                              </span>
                            <?php elseif ($appliesTo === 'employee'): ?>
                              <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">
                                <i class="fa-solid fa-user me-1"></i> Emp: <?= htmlspecialchars(($h['employee_code'] ?? '') . ' - ' . ($h['employee_name'] ?? '')) ?>
                              </span>
                            <?php else: ?>
                              <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                <i class="fa-solid fa-users me-1"></i> All Employees
                              </span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <div class="d-flex align-items-center gap-10">
                              <!-- Edit Button -->
                              <button type="button" class="table__icon edit"
                                data-bs-toggle="modal"
                                data-bs-target="#editHolidayModal"
                                data-id="<?= $h['id'] ?>"
                                data-title="<?= htmlspecialchars($h['title']) ?>"
                                data-start-date="<?= $h['start_date'] ?>"
                                data-end-date="<?= $h['end_date'] ?>"
                                data-applies-to="<?= htmlspecialchars($appliesTo) ?>"
                                data-department-id="<?= htmlspecialchars($h['department_id'] ?? '') ?>"
                                data-employee-id="<?= htmlspecialchars($h['employee_id'] ?? '') ?>">
                                <i class="fa-sharp fa-light fa-pen"></i>
                              </button>
                              <!-- Delete Button -->
                              <button class="removeBtnCust table__icon delete"
                                data-id="<?= $h['id'] ?>"
                                data-name="<?= htmlspecialchars($h['title']) ?>"
                                data-action="delete-holiday">
                                <i class="fa-regular fa-trash"></i>
                              </button>
                            </div>
                          </td>
                        </tr>
                    <?php endfor;
                    endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Tab 2: Tipsoi Holidays -->
    <div class="tab-pane fade" id="tipsoi-holidays" role="tabpanel" aria-labelledby="tipsoi-holidays-tab">
      <div class="row">
        <div class="card__wrapper">
          <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
              <input type="text" id="searchTipsoiHoliday" class="form-control" placeholder="Search Holiday..." style="max-width: 250px;">
            </div>
            <button type="button" id="btnSyncTipsoiHolidays" class="btn btn-success d-flex align-items-center gap-2">
              <i class="fas fa-sync-alt" id="syncHolidaysSpinnerIcon"></i> Sync from Tipsoi
            </button>
          </div>
          
          <div class="table__wrapper table-responsive">
            <table class="table mb-20">
              <thead>
                <tr class="table__title">
                  <th style="width: 50px; text-align: center;">SN</th>
                  <th style="width: 150px;">Date</th>
                  <th style="width: 150px;">Day</th>
                  <th>Holiday Name</th>
                  <th style="width: 180px; text-align: center;">Action</th>
                </tr>
              </thead>
              <tbody class="table__body" id="tipsoiHolidaysTableBody">
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">
                    <i class="fas fa-info-circle me-1"></i> Click <strong>Sync from Tipsoi</strong> to load holidays.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- new Holiday modal start -->
  <div id="addNewHolidays" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">New Holidays</h5>
          <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close"><i
              class="fa-solid fa-xmark-large"></i></button>
        </div>
        <div class="modal-body">
          <form action="<?= $GLOBALS['base_url'] ?>/store-holiday" method="POST">

            <div class="row gy-20">

              <!-- FROM DATE -->
              <div class="col-md-12">
                <div class="from__input-box">
                  <div class="form__input-title">
                    <label>From Date <span class="text-danger">*</span></label>
                  </div>
                  <div class="form__input">
                    <input type="date" name="start_date" class="form-control" required>
                  </div>
                </div>
              </div>

              <!-- TO DATE -->
              <div class="col-md-12">
                <div class="from__input-box">
                  <div class="form__input-title">
                    <label>To Date <span class="text-danger">*</span></label>
                  </div>
                  <div class="form__input">
                    <input type="date" name="end_date" class="form-control" required>
                  </div>
                </div>
              </div>

              <!-- HOLIDAY NAME -->
              <div class="col-md-12">
                <div class="from__input-box">
                  <div class="form__input-title">
                    <label>Holiday Name <span class="text-danger">*</span></label>
                  </div>
                  <div class="form__input">
                    <input type="text" name="title" class="form-control" placeholder="e.g. Eid Holiday, Special Company Off" required>
                  </div>
                </div>
              </div>

              <!-- APPLIES TO / SCOPE -->
              <div class="col-md-12">
                <div class="from__input-box">
                  <div class="form__input-title">
                    <label>Holiday For / Target <span class="text-danger">*</span></label>
                  </div>
                  <div class="form__input">
                    <select name="applies_to" id="add_applies_to" class="form-select form-control" required>
                      <option value="all">All Employees (Company-wide)</option>
                      <option value="department">Specific Department</option>
                      <option value="employee">Specific Employee</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- DEPARTMENT SELECT -->
              <div class="col-md-12" id="add_department_group" style="display: none;">
                <div class="from__input-box">
                  <div class="form__input-title">
                    <label>Select Department <span class="text-danger">*</span></label>
                  </div>
                  <div class="form__input">
                    <select name="department_id" id="add_department_id" class="form-select form-control">
                      <option value="">-- Select Department --</option>
                      <?php if (!empty($departments)): ?>
                        <?php foreach ($departments as $dept): ?>
                          <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </select>
                  </div>
                </div>
              </div>

              <!-- EMPLOYEE SELECT (Multiple) -->
              <div class="col-md-12" id="add_employee_group" style="display: none;">
                <div class="from__input-box">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0 fw-bold">Select Employee(s) <span class="text-danger">*</span></label>
                    <div class="d-flex gap-2">
                      <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 fs-12" id="btnSelectAllEmployees">Select All</button>
                      <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 fs-12" id="btnClearAllEmployees">Clear</button>
                    </div>
                  </div>
                  <!-- Quick department auto-select helper -->
                  <div class="mb-2">
                    <select id="helper_filter_by_dept" class="form-select form-select-sm">
                      <option value="">⚡ Quick select all from department...</option>
                      <?php if (!empty($departments)): ?>
                        <?php foreach ($departments as $dept): ?>
                          <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </select>
                  </div>
                  <div class="form__input">
                    <select name="employee_ids[]" id="add_employee_ids" class="form-select form-control" multiple="multiple" style="min-height: 160px;">
                      <?php if (!empty($employees)): ?>
                        <?php foreach ($employees as $emp): ?>
                          <option value="<?= $emp['id'] ?>" data-dept="<?= $emp['department_id'] ?? '' ?>">
                            <?= htmlspecialchars($emp['employee_id'] . ' - ' . $emp['full_name']) ?> (<?= htmlspecialchars($emp['department_name'] ?? 'N/A') ?>)
                          </option>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </select>
                    <small class="text-muted d-block mt-1">Hold Ctrl (or Cmd) to pick multiple employees, or use the Quick Select buttons above.</small>
                    <div class="mt-1 fw-semibold text-primary fs-12" id="selectedEmpCounter">Selected: 0 employee(s)</div>
                  </div>
                </div>
              </div>

            </div>

            <div class="submit__btn text-center mt-3">
              <button type="submit" class="btn btn-primary">Submit</button>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- new Holiday modal start -->

  <!-- Edit Holiday modal start -->
  <div id="editHolidayModal" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Holiday</h5>
          <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close"><i
              class="fa-solid fa-xmark-large"></i></button>
        </div>
        <div class="modal-body">
          <form action="<?= $GLOBALS['base_url'] ?>/update-holiday" method="POST">
            <input type="hidden" name="id" id="edit_holiday_id">

            <div class="row gy-20">

              <!-- FROM DATE -->
              <div class="col-md-12">
                <div class="from__input-box">
                  <div class="form__input-title"><label>From Date <span class="text-danger">*</span></label></div>
                  <div class="form__input">
                    <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
                  </div>
                </div>
              </div>

              <!-- TO DATE -->
              <div class="col-md-12">
                <div class="from__input-box">
                  <div class="form__input-title"><label>To Date <span class="text-danger">*</span></label></div>
                  <div class="form__input">
                    <input type="date" name="end_date" id="edit_end_date" class="form-control" required>
                  </div>
                </div>
              </div>

              <!-- HOLIDAY NAME -->
              <div class="col-md-12">
                <div class="from__input-box">
                  <div class="form__input-title"><label>Holiday Name <span class="text-danger">*</span></label></div>
                  <div class="form__input">
                    <input type="text" name="title" id="edit_title" class="form-control" required>
                  </div>
                </div>
              </div>

              <!-- APPLIES TO / SCOPE -->
              <div class="col-md-12">
                <div class="from__input-box">
                  <div class="form__input-title"><label>Holiday For / Target <span class="text-danger">*</span></label></div>
                  <div class="form__input">
                    <select name="applies_to" id="edit_applies_to" class="form-select form-control" required>
                      <option value="all">All Employees (Company-wide)</option>
                      <option value="department">Specific Department</option>
                      <option value="employee">Specific Employee</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- DEPARTMENT SELECT -->
              <div class="col-md-12" id="edit_department_group" style="display: none;">
                <div class="from__input-box">
                  <div class="form__input-title"><label>Select Department <span class="text-danger">*</span></label></div>
                  <div class="form__input">
                    <select name="department_id" id="edit_department_id" class="form-select form-control">
                      <option value="">-- Select Department --</option>
                      <?php if (!empty($departments)): ?>
                        <?php foreach ($departments as $dept): ?>
                          <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </select>
                  </div>
                </div>
              </div>

              <!-- EMPLOYEE SELECT -->
              <div class="col-md-12" id="edit_employee_group" style="display: none;">
                <div class="from__input-box">
                  <div class="form__input-title"><label>Select Employee <span class="text-danger">*</span></label></div>
                  <div class="form__input">
                    <select name="employee_id" id="edit_employee_id" class="form-select form-control">
                      <option value="">-- Select Employee --</option>
                      <?php if (!empty($employees)): ?>
                        <?php foreach ($employees as $emp): ?>
                          <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['employee_id'] . ' - ' . $emp['full_name']) ?></option>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </select>
                  </div>
                </div>
              </div>

            </div>

            <div class="submit__btn text-center mt-3">
              <button type="submit" class="btn btn-primary">Update</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- edit Holiday modal end -->


</div><!-- /content -->
<script>
  const base_url = "<?= $GLOBALS['base_url'] ?>"; // PHP outputs the correct base URL
  document.addEventListener("DOMContentLoaded", function() {
    // -------------------------------------------------------------
    // URL Tab parameter check
    // -------------------------------------------------------------
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab');
    if (activeTab === 'tipsoi') {
      const tabEl = document.getElementById('tipsoi-holidays-tab');
      if (tabEl) {
        const tab = new bootstrap.Tab(tabEl);
        tab.show();
      }
    }

    // -------------------------------------------------------------
    // Add Holiday Modal: Scope dynamic toggle & Multi-select helpers
    // -------------------------------------------------------------
    const addAppliesTo = document.getElementById('add_applies_to');
    const addDeptGroup = document.getElementById('add_department_group');
    const addEmpGroup = document.getElementById('add_employee_group');
    const addDeptSelect = document.getElementById('add_department_id');
    const addEmpSelect = document.getElementById('add_employee_ids');
    const btnSelectAll = document.getElementById('btnSelectAllEmployees');
    const btnClearAll = document.getElementById('btnClearAllEmployees');
    const helperDept = document.getElementById('helper_filter_by_dept');
    const counterEl = document.getElementById('selectedEmpCounter');

    function updateEmpCounter() {
      if (!addEmpSelect || !counterEl) return;
      let count = 0;
      for (let i = 0; i < addEmpSelect.options.length; i++) {
        if (addEmpSelect.options[i].selected) count++;
      }
      counterEl.textContent = `Selected: ${count} employee(s)`;
    }

    if (addEmpSelect) {
      addEmpSelect.addEventListener('change', updateEmpCounter);
    }

    if (btnSelectAll && addEmpSelect) {
      btnSelectAll.addEventListener('click', function() {
        for (let i = 0; i < addEmpSelect.options.length; i++) {
          addEmpSelect.options[i].selected = true;
        }
        updateEmpCounter();
      });
    }

    if (btnClearAll && addEmpSelect) {
      btnClearAll.addEventListener('click', function() {
        for (let i = 0; i < addEmpSelect.options.length; i++) {
          addEmpSelect.options[i].selected = false;
        }
        updateEmpCounter();
      });
    }

    if (helperDept && addEmpSelect) {
      helperDept.addEventListener('change', function() {
        const deptId = this.value;
        if (!deptId) return;
        for (let i = 0; i < addEmpSelect.options.length; i++) {
          const optDept = addEmpSelect.options[i].getAttribute('data-dept');
          if (optDept == deptId) {
            addEmpSelect.options[i].selected = true;
          }
        }
        updateEmpCounter();
      });
    }

    if (addAppliesTo) {
      addAppliesTo.addEventListener('change', function() {
        const val = this.value;
        if (val === 'department') {
          addDeptGroup.style.display = 'block';
          addEmpGroup.style.display = 'none';
          if (addDeptSelect) addDeptSelect.required = true;
          if (addEmpSelect) addEmpSelect.required = false;
        } else if (val === 'employee') {
          addDeptGroup.style.display = 'none';
          addEmpGroup.style.display = 'block';
          if (addDeptSelect) addDeptSelect.required = false;
          if (addEmpSelect) addEmpSelect.required = true;
          updateEmpCounter();
        } else {
          addDeptGroup.style.display = 'none';
          addEmpGroup.style.display = 'none';
          if (addDeptSelect) addDeptSelect.required = false;
          if (addEmpSelect) addEmpSelect.required = false;
        }
      });
    }

    // -------------------------------------------------------------
    // Edit Modal Binding & Scope Toggle
    // -------------------------------------------------------------
    const modal = document.getElementById('editHolidayModal');
    const editAppliesTo = document.getElementById('edit_applies_to');
    const editDeptGroup = document.getElementById('edit_department_group');
    const editEmpGroup = document.getElementById('edit_employee_group');
    const editDeptSelect = document.getElementById('edit_department_id');
    const editEmpSelect = document.getElementById('edit_employee_id');

    function toggleEditScope(val) {
      if (val === 'department') {
        editDeptGroup.style.display = 'block';
        editEmpGroup.style.display = 'none';
        editDeptSelect.required = true;
        editEmpSelect.required = false;
      } else if (val === 'employee') {
        editDeptGroup.style.display = 'none';
        editEmpGroup.style.display = 'block';
        editDeptSelect.required = false;
        editEmpSelect.required = true;
      } else {
        editDeptGroup.style.display = 'none';
        editEmpGroup.style.display = 'none';
        editDeptSelect.required = false;
        editEmpSelect.required = false;
      }
    }

    if (editAppliesTo) {
      editAppliesTo.addEventListener('change', function() {
        toggleEditScope(this.value);
      });
    }

    modal.addEventListener('show.bs.modal', function(event) {
      const button = event.relatedTarget;
      if (!button) return;

      const id = button.getAttribute('data-id');
      const title = button.getAttribute('data-title');
      const start = button.getAttribute('data-start-date');
      const end = button.getAttribute('data-end-date');
      const appliesTo = button.getAttribute('data-applies-to') || 'all';
      const deptId = button.getAttribute('data-department-id') || '';
      const empId = button.getAttribute('data-employee-id') || '';

      document.getElementById('edit_holiday_id').value = id;
      document.getElementById('edit_title').value = title;
      document.getElementById('edit_start_date').value = start;
      document.getElementById('edit_end_date').value = end;
      
      if (editAppliesTo) {
        editAppliesTo.value = appliesTo;
        editDeptSelect.value = deptId;
        editEmpSelect.value = empId;
        toggleEditScope(appliesTo);
      }
    });

    // -------------------------------------------------------------
    // Tipsoi Holidays Integration & Stats
    // -------------------------------------------------------------
    const tipsoiTabBtn = document.getElementById('tipsoi-holidays-tab');
    const localTabBtn = document.getElementById('local-holidays-tab');
    const syncHolidaysBtn = document.getElementById('btnSyncTipsoiHolidays');
    const syncHolidaysSpinner = document.getElementById('syncHolidaysSpinnerIcon');
    const tipsoiTableBody = document.getElementById('tipsoiHolidaysTableBody');
    const searchInput = document.getElementById('searchTipsoiHoliday');
    
    let tipsoiHolidaysList = [];

    const localStats = {
      totalYear: <?= $totalYearHoliday ?>,
      thisMonth: <?= $thisMonthHoliday ?>,
      weeklyOff: <?= $weeklyHolidayCount ?>,
      upcoming: <?= $upcomingHolidayCount ?>
    };

    function updateStatCards(stats) {
      document.getElementById('cardTotalHolidays').textContent = stats.totalYear;
      document.getElementById('cardThisMonthHolidays').textContent = stats.thisMonth;
      document.getElementById('cardWeeklyOff').textContent = stats.weeklyOff;
      document.getElementById('cardUpcomingHolidays').textContent = stats.upcoming;
    }

    function calculateTipsoiStats() {
      const today = new Date();
      today.setHours(0,0,0,0);
      const currentYear = today.getFullYear();
      const currentMonth = today.getMonth(); // 0-11
      
      const next30Days = new Date();
      next30Days.setDate(today.getDate() + 30);
      next30Days.setHours(23,59,59,999);

      let totalYear = 0;
      let thisMonth = 0;
      let upcoming = 0;

      tipsoiHolidaysList.forEach(h => {
        const hDate = new Date(h.date);
        hDate.setHours(0,0,0,0);

        if (hDate.getFullYear() === currentYear) {
          totalYear++;
        }
        if (hDate.getFullYear() === currentYear && hDate.getMonth() === currentMonth) {
          thisMonth++;
        }
        if (hDate >= today && hDate <= next30Days) {
          upcoming++;
        }
      });

      return {
        totalYear: totalYear,
        thisMonth: thisMonth,
        weeklyOff: localStats.weeklyOff,
        upcoming: upcoming
      };
    }

    // Sync button event listener
    syncHolidaysBtn.addEventListener('click', function(e) {
      e.preventDefault();
      fetchTipsoiHolidays();
    });

    // Tab switch listeners
    localTabBtn.addEventListener('shown.bs.tab', function() {
      updateStatCards(localStats);
    });

    tipsoiTabBtn.addEventListener('shown.bs.tab', function() {
      if (tipsoiHolidaysList.length === 0) {
        updateStatCards({ totalYear: '—', thisMonth: '—', weeklyOff: localStats.weeklyOff, upcoming: '—' });
        fetchTipsoiHolidays();
      } else {
        updateStatCards(calculateTipsoiStats());
      }
    });

    // Search filter
    searchInput.addEventListener('input', function() {
      renderTipsoiHolidays();
    });

    function fetchTipsoiHolidays() {
      syncHolidaysBtn.disabled = true;
      syncHolidaysSpinner.classList.add('fa-spin');
      tipsoiTableBody.innerHTML = `
        <tr>
          <td colspan="5" class="text-center py-4">
            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
            Loading holidays from Tipsoi...
          </td>
        </tr>
      `;

      fetch(`${base_url}/api/hrm/tipsoi-holidays`)
        .then(res => res.json())
        .then(response => {
          if (response.success) {
            tipsoiHolidaysList = response.data || [];
            renderTipsoiHolidays();
            
            // Update stats cards dynamically if Tipsoi tab is active
            if (tipsoiTabBtn.classList.contains('active')) {
              updateStatCards(calculateTipsoiStats());
            }
          } else {
            showErrorRow(response.message || "Failed to load Tipsoi holidays.");
          }
        })
        .catch(err => {
          showErrorRow("Network error occurred while fetching holidays.");
        })
        .finally(() => {
          syncHolidaysBtn.disabled = false;
          syncHolidaysSpinner.classList.remove('fa-spin');
        });
    }

    function showErrorRow(msg) {
      tipsoiTableBody.innerHTML = `
        <tr>
          <td colspan="5" class="text-center text-danger py-4">
            <i class="fas fa-exclamation-triangle me-2"></i> ${msg}
          </td>
        </tr>
      `;
    }

    function renderTipsoiHolidays() {
      const searchVal = searchInput.value.toLowerCase().trim();
      const filtered = tipsoiHolidaysList.filter(h => {
        return h.title.toLowerCase().includes(searchVal) || h.date.includes(searchVal);
      });

      if (filtered.length === 0) {
        tipsoiTableBody.innerHTML = `
          <tr>
            <td colspan="5" class="text-center py-4 text-muted">
              <i class="fas fa-info-circle me-1"></i> No matching holidays found.
            </td>
          </tr>
        `;
        return;
      }

      let html = '';
      filtered.forEach((h, index) => {
        const dateObj = new Date(h.date);
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const dayName = days[dateObj.getDay()];
        
        const dateOptions = { day: 'numeric', month: 'long', year: 'numeric' };
        const dateDisplay = dateObj.toLocaleDateString('en-GB', dateOptions);

        let actionHtml = '';
        if (h.is_imported) {
          actionHtml = `<span class="badge bg-success px-3 py-2"><i class="fas fa-check-circle me-1"></i> Imported</span>`;
        } else {
          actionHtml = `
            <button type="button" class="btn btn-sm btn-outline-primary btn-import-holiday d-flex align-items-center gap-1 mx-auto" 
              data-title="${h.title}" data-date="${h.date}">
              <i class="fas fa-download"></i> Import to ERP
            </button>
          `;
        }

        html += `
          <tr>
            <td class="text-center text-muted fw-semibold">${index + 1}</td>
            <td class="table__date">${dateDisplay}</td>
            <td class="table__day">${dayName}</td>
            <td class="table__name fw-bold">${h.title}</td>
            <td class="text-center">${actionHtml}</td>
          </tr>
        `;
      });

      tipsoiTableBody.innerHTML = html;

      // Add event listeners to import buttons
      document.querySelectorAll('.btn-import-holiday').forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          const targetBtn = this;
          const title = targetBtn.getAttribute('data-title');
          const date = targetBtn.getAttribute('data-date');
          
          importHolidayToErp(targetBtn, title, date);
        });
      });
    }

    function importHolidayToErp(button, title, date) {
      button.disabled = true;
      button.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status"></span> Importing...`;

      fetch(`${base_url}/api/hrm/import-tipsoi-holiday`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ title, date })
      })
      .then(res => res.json())
      .then(response => {
        if (response.success) {
          Swal.fire({
            title: 'Imported!',
            text: response.message,
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
          });
          
          // Update local status in array
          const holidayItem = tipsoiHolidaysList.find(h => h.title === title && h.date === date);
          if (holidayItem) {
            holidayItem.is_imported = true;
          }
          
          // Update localStats values dynamically
          localStats.totalYear++;
          const dateObj = new Date(date);
          const currentYear = new Date().getFullYear();
          if (dateObj.getFullYear() === currentYear) {
            if (dateObj.getMonth() === new Date().getMonth()) {
              localStats.thisMonth++;
            }
            const today = new Date();
            today.setHours(0,0,0,0);
            const next30 = new Date();
            next30.setDate(today.getDate() + 30);
            next30.setHours(23,59,59,999);
            if (dateObj >= today && dateObj <= next30) {
              localStats.upcoming++;
            }
          }

          renderTipsoiHolidays();
        } else {
          Swal.fire({
            title: 'Error',
            text: response.message || 'Failed to import holiday.',
            icon: 'error'
          });
          button.disabled = false;
          button.innerHTML = `<i class="fas fa-download"></i> Import to ERP`;
        }
      })
      .catch(err => {
        Swal.fire({
          title: 'Network Error',
          text: 'An error occurred while importing holiday.',
          icon: 'error'
        });
        button.disabled = false;
        button.innerHTML = `<i class="fas fa-download"></i> Import to ERP`;
      });
    }
  });
</script>