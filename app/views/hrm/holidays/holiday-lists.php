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
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody class="table__body">
                    <?php foreach ($holidays as $h):
                      $start = strtotime($h['start_date']);
                      $end   = strtotime($h['end_date']);
                      $title = htmlspecialchars($h['title']);

                      for ($d = $start; $d <= $end; $d += 86400):
                        $dateStr = date('Y-m-d', $d);
                        $dayName = date('l', $d);
                        $dateDisplay = date('j F Y', $d);
                    ?>
                        <tr>
                          <td class="table__date"><?= $dateDisplay ?></td>
                          <td class="table__day"><?= $dayName ?></td>
                          <td class="table__name"><?= $title ?></td>
                          <td>
                            <div class="d-flex align-items-center gap-10">
                              <!-- Edit Button -->
                              <button type="button" class="table__icon edit"
                                data-bs-toggle="modal"
                                data-bs-target="#editHolidayModal"
                                data-id="<?= $h['id'] ?>"
                                data-title="<?= htmlspecialchars($h['title']) ?>"
                                data-start-date="<?= $h['start_date'] ?>"
                                data-end-date="<?= $h['end_date'] ?>">
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
                    <label>From Date</label>
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
                    <label>To Date</label>
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
                    <label>Holiday Name</label>
                  </div>
                  <div class="form__input">
                    <input type="text" name="title" class="form-control" required>
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
          <h5 class="modal-title">New Holidays</h5>
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
                  <div class="form__input-title"><label>From Date</label></div>
                  <div class="form__input">
                    <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
                  </div>
                </div>
              </div>

              <!-- TO DATE -->
              <div class="col-md-12">
                <div class="from__input-box">
                  <div class="form__input-title"><label>To Date</label></div>
                  <div class="form__input">
                    <input type="date" name="end_date" id="edit_end_date" class="form-control" required>
                  </div>
                </div>
              </div>

              <!-- HOLIDAY NAME -->
              <div class="col-md-12">
                <div class="from__input-box">
                  <div class="form__input-title"><label>Holiday Name</label></div>
                  <div class="form__input">
                    <input type="text" name="title" id="edit_title" class="form-control" required>
                  </div>
                </div>
              </div>

              <!-- DESCRIPTION -->
              <div class="col-md-12">
                <div class="from__input-box">
                  <div class="form__input-title"><label>Description (optional)</label></div>
                  <div class="form__input">
                    <textarea name="description" id="edit_description" class="form-control"></textarea>
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
    // Edit Modal Binding
    // -------------------------------------------------------------
    const modal = document.getElementById('editHolidayModal');
    modal.addEventListener('show.bs.modal', function(event) {
      const button = event.relatedTarget;

      const id = button.getAttribute('data-id');
      const title = button.getAttribute('data-title');
      const start = button.getAttribute('data-start-date');
      const end = button.getAttribute('data-end-date');
      const desc = button.getAttribute('data-description') ?? '';

      document.getElementById('edit_holiday_id').value = id;
      document.getElementById('edit_title').value = title;
      document.getElementById('edit_start_date').value = start;
      document.getElementById('edit_end_date').value = end;
      document.getElementById('edit_description').value = desc;
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