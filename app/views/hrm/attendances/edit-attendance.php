<?php
$page_title = "Attendance Edit";
$page = "Edit Attendance";
$show_breadcrumb = true;

// $attendance: current attendance data from controller
?>

<div class="row">
    <div class="card__wrapper">

        <form action="<?= $GLOBALS['base_url'] ?>/update-attendance/<?= $attendance['id'] ?>" method="POST">
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
                                                 <select class="form-control select2" name="employee_id" id="employee_id" required>
                                                     <option value="">Select Employee</option>
                                                     <?php foreach ($employees as $emp): ?>
                                                         <option value="<?= $emp['id'] ?>" <?= ($emp['id'] == $attendance['employee_id']) ? 'selected' : '' ?>>
                                                             <?= htmlspecialchars($emp['full_name'] ?? '') ?> (<?= htmlspecialchars($emp['employee_id'] ?? '') ?>)
                                                         </option>
                                                     <?php endforeach ?>
                                                 </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Today Status -->
                                    <div class="col-lg-6 col-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="today_status">Today Employee Status <span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <select class="form-control" name="today_status" required>
                                                    <option value="">-- Select Status --</option>
                                                    <?php
                                                    $statuses = ['Present', 'Absent', 'Late', 'Half-Day', 'On Leave', 'Holiday', 'Day Off'];
                                                    foreach ($statuses as $status):
                                                    ?>
                                                        <option value="<?= $status ?>" <?= ($status == $attendance['today_status']) ? 'selected' : '' ?>>
                                                            <?= $status ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Attendance Date -->
                                    <div class="col-lg-6 col-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="attendance_date">Date <span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="attendance_date" id="attendance_date" type="date"
                                                    value="<?= $attendance['attendance_date'] ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Punched In -->
                                    <div class="col-lg-6 col-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="punch_in_time">Punched In <span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="punch_in_time" id="punch_in_time" type="time"
                                                    value="<?= !empty($attendance['punch_in_time']) && $attendance['punch_in_time'] != '0000-00-00 00:00:00' ? date('H:i', strtotime($attendance['punch_in_time'])) : '' ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Punched Out -->
                                    <div class="col-lg-6 col-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="punch_out_time">Punched Out <span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="punch_out_time" id="punch_out_time" type="time"
                                                    value="<?= !empty($attendance['punch_out_time']) && $attendance['punch_out_time'] != '0000-00-00 00:00:00' ? date('H:i', strtotime($attendance['punch_out_time'])) : '' ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="submit__btn text-center mb-20">
                                        <button class="btn btn-primary">Update Attendance</button>
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