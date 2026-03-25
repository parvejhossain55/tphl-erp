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
        <div class="value"><?= $totalYearHoliday ?></div>
        <div class="label">Total Holidays (<?= date('Y') ?>)</div>

      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card card__wrapper">
        <div class="card__icon"><i class="fas fa-calendar-check" style="color:#3b82f6"></i></div>
        <div class="value"><?= $thisMonthHoliday ?></div>
        <div class="label">This Month Holidays</div>

      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card card__wrapper">
        <div class="card__icon"><i class="fas fa-sun" style="color:#10b981"></i></div>
        <div class="value"><?= $weeklyHolidayCount ?></div>
        <div class="label">Weekly Off</div>
        <div class="change up"><i class="fas fa-check me-1"></i>Total Year</div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card card__wrapper">
        <div class="card__icon"><i class="fas fa-mosque" style="color:#f59e0b"></i></div>
        <div class="value"><?= $upcomingHolidayCount ?></div>
        <div class="label">Upcoming Holidays</div>
        <div class="change up"><i class="fas fa-clock me-1"></i>Next 30 Days</div>
      </div>
    </div>
  </div>


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
  });
</script>