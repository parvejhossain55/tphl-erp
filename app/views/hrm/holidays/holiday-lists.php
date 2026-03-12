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
  <div class="row  mb-4">
    <div class="col-6 col-xl-3">
      <div class="stat-card">
        <div class="stat-icon" style="background:#fff0f3"><i class="fas fa-flag" style="color:var(--accent)"></i></div>
        <div class="value">২২</div>
        <div class="label">মোট ছুটির দিন (২০২৫)</div>
        <div class="change up"><i class="fas fa-arrow-up me-1"></i>গত বছরের চেয়ে ২ বেশি</div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card">
        <div class="stat-icon" style="background:#f0f9ff"><i class="fas fa-calendar-check" style="color:#3b82f6"></i></div>
        <div class="value">৫</div>
        <div class="label">এই মাসের ছুটি</div>
        <div class="change down"><i class="fas fa-arrow-down me-1"></i>গত মাসের চেয়ে কম</div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card">
        <div class="stat-icon" style="background:#f0fdf4"><i class="fas fa-sun" style="color:#10b981"></i></div>
        <div class="value">৫২</div>
        <div class="label">সাপ্তাহিক ছুটি (শুক্র)</div>
        <div class="change up"><i class="fas fa-check me-1"></i>সারা বছর</div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card">
        <div class="stat-icon" style="background:#fffbeb"><i class="fas fa-mosque" style="color:#f59e0b"></i></div>
        <div class="value">৩</div>
        <div class="label">আসন্ন ছুটি</div>
        <div class="change up"><i class="fas fa-clock me-1"></i>পরবর্তী ৩০ দিনে</div>
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
                </tr>
              </thead>
              <tbody class="table__body">
                <tr>
                  <td class="table__date">1 January 2024</td>
                  <td class="table__day">Tuesday</td>
                  <td class="table__name">New Years Day</td>
                </tr>
                <tr>
                  <td class="table__date">14 February 2024</td>
                  <td class="table__day">Thursday</td>
                  <td class="table__name">Valentines Day</td>
                </tr>
                <tr>
                  <td class="table__date">17 March 2024</td>
                  <td class="table__day">Sunday</td>
                  <td class="table__name">St. Patricks Day</td>
                </tr>
                <tr>
                  <td class="table__date">12 April 2024</td>
                  <td class="table__day">Friday</td>
                  <td class="table__name">Easter Sunday</td>
                </tr>
                <tr>
                  <td class="table__date">1 May 2024</td>
                  <td class="table__day">Wednesday</td>
                  <td class="table__name">International WorkersDay</td>
                </tr>
                <tr>
                  <td class="table__date">6 May 2024</td>
                  <td class="table__day">Monday</td>
                  <td class="table__name">Ramadan Begins</td>
                </tr>
                <tr>
                  <td class="table__date">12 June 2024</td>
                  <td class="table__day">Wednesday</td>
                  <td class="table__name">World Day Against Child Labor</td>
                </tr>
                <tr>
                  <td class="table__date">21 June 2024</td>
                  <td class="table__day">Friday</td>
                  <td class="table__name">International Day of Yoga</td>
                </tr>
                <tr>
                  <td class="table__date">4 July 2024</td>
                  <td class="table__day">Thursday</td>
                  <td class="table__name">Independence Day (United States)</td>
                </tr>
                <tr>
                  <td class="table__date">14 August 2024</td>
                  <td class="table__day">Wednesday</td>
                  <td class="table__name">Palestine Independence Day</td>
                </tr>
                <tr>
                  <td class="table__date">15 August 2024</td>
                  <td class="table__day">Thursday</td>
                  <td class="table__name">Zealandia Independence Day</td>
                </tr>
                <tr>
                  <td class="table__date">1 September 2024</td>
                  <td class="table__day">Sunday</td>
                  <td class="table__name">Islamic New Year</td>
                </tr>
                <tr>
                  <td class="table__date">21 September 2024</td>
                  <td class="table__day">Saturday</td>
                  <td class="table__name">International Day of Peace</td>
                </tr>
                <tr>
                  <td class="table__date">1 October 2024</td>
                  <td class="table__day">Tuesday</td>
                  <td class="table__name">International Day of Older Persons</td>
                </tr>
                <tr>
                  <td class="table__date">24 October 2024</td>
                  <td class="table__day">Thursday</td>
                  <td class="table__name">United Nations Day</td>
                </tr>
                <tr>
                  <td class="table__date">31 October 2024</td>
                  <td class="table__day">Thursday</td>
                  <td class="table__name">Halloween</td>
                </tr>
                <tr>
                  <td class="table__date">9 November 2024</td>
                  <td class="table__day">Saturday</td>
                  <td class="table__name">World Freedom Day</td>
                </tr>
                <tr>
                  <td class="table__date">20 November 2024</td>
                  <td class="table__day">Wednesday</td>
                  <td class="table__name">Universal Childrens Day</td>
                </tr>
                <tr>
                  <td class="table__date">1 December 2024</td>
                  <td class="table__day">Sunday</td>
                  <td class="table__name">World AIDS Day</td>
                </tr>
                <tr>
                  <td class="table__date">10 December 2024</td>
                  <td class="table__day">Tuesday</td>
                  <td class="table__name">Human Rights Day</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- new Leave modal start -->
  <div id="addNewHolidays" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">New Holidays</h5>
          <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close"><i
              class="fa-solid fa-xmark-large"></i></button>
        </div>
        <div class="modal-body">
          <form action="#">
            <div class="row gy-20">
              <div class="col-xl-12">
                <div class="card__wrapper mb-20">
                  <div class="row gy-20">
                    <div class="col-md-12">
                      <div class="from__input-box">
                        <div class="form__input-title">
                          <label class="form-check-label" for="dateDuration">Holiday Date</label>
                        </div>
                        <div class="form__input">
                          <input name="fromDate" class="form-control" id="dateDuration" type="text" required>
                        </div>
                      </div>
                    </div>
               
                    <div class="col-md-12">
                      <div class="from__input-box">
                        <div class="form__input-title">
                          <label>Holiday Name <span>*</span></label>
                        </div>
                        <div class="form__input">
                          <textarea class="form-control" name="name" required></textarea>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="submit__btn text-center">
              <button class="btn btn-primary">Submit</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- new Leave modal end -->


</div><!-- /content -->