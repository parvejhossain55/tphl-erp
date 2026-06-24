<?php
$page_title = "Attendance Create";
$page = "Add Attendance";
$show_breadcrumb = true;

?>
<div class="row">
    <div class="card__wrapper">


        <form action="<?= $GLOBALS['base_url'] ?>/store-attendance" method="POST">
            <div class="row">

                <div class="col-xxl-12">
                    <div class="row gy-10">

                        <div class="col-xl-12">
                            <div class="card__wrapper">

                                <div class="row gy-20">

                                    <!-- Employee -->
                                    <div class="col-lg-6 col-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="employee">Employee <span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                 <select class="form-control select2" name="employee_id" id="employee_id">
                                                     <option value="">Select Employee</option>
                                                     <?php foreach ($employees as $emp): ?>
                                                         <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name'] ?? '') ?> (<?= htmlspecialchars($emp['employee_id'] ?? '') ?>)</option>

                                                     <?php endforeach ?>
                                                 </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Punch -->
                                    <div class="col-lg-6 col-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="punch">Today Employee Status <span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <select class="form-control" name="today_status" required id="today_status">
                                                    <option value="">-- Select Status --</option>
                                                    <option value="Present">Present</option>
                                                    <option value="Absent">Absent</option>
                                                    <option value="Late">Late</option>
                                                    <option value="Half-Day">Half-Day</option>
                                                    <option value="On Leave">On Leave</option>
                                                    <option value="Holiday">Holiday</option>
                                                    <option value="Day Off">Day Off</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Date -->
                                    <div class="col-lg-6 col-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="date">Date <span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="attendance_date" id="date" type="date">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Punched In -->
                                    <div class="col-lg-6 col-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="punched_at">Punched In <span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="punch_in_time" id="punched_in" type="time" id="punched_in">
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Punched Out -->
                                    <div class="col-lg-6 col-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="punched_out">Punched Out <span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="punch_out_time" id="punched_out" type="time" id="punched_out">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="submit__btn text-center mb-20">
                                        <button class="btn btn-primary">Add Attendance</button>
                                          <a href="<?= $GLOBALS['base_url'] ?>/attendance-list"
                                            class="btn btn-outline-secondary px-4 ms-sm-auto">
                                            <i class="fas fa-arrow-left"></i> Back
                                        </a>
                                    </div>

                                </div> <!-- row end -->

                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </div>

</div>
<script>
document.addEventListener("DOMContentLoaded", function () {

    const status = document.getElementById("today_status");
    const punchIn = document.getElementById("punched_in");
    const punchOut = document.getElementById("punched_out");

    status.addEventListener("change", function () {

        if (this.value === "Absent") {

            punchIn.value = "";
            punchOut.value = "";

            punchIn.disabled = true;
            punchOut.disabled = true;

        } else {

            punchIn.disabled = false;
            punchOut.disabled = false;

        }

    });

});
</script>