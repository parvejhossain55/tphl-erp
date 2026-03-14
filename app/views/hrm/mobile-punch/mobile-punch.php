<div class="row">
    <div class="card__wrapper">

        <form method="POST" action="<?= $GLOBALS['base_url'] ?>/store-employee-leave">

                <div class="row gy-20">
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
                <button class="btn btn-primary">Submit</button>
                <a href="<?= $GLOBALS['base_url'] ?>/attendance-list"
                    class="btn btn-outline-secondary px-4 ms-sm-auto">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </form>
    </div>
</div>
<div class="card__wrapper p-4">
    <h4 class="mb-3">My Monthly Punch Report</h4>

    <table class="table table-bordered table-striped">
        <tr class="table__title">
            <th>Leave Type</th>
            <th>Leave Duration</th>
            <th>Days</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Applied At</th>
        </tr>
        </thead>
        <tbody>

      

                <tr>
                    <td></td>

                    <td class="table__leave-duration"></td>
                    <td class="table__leave-days"></td>
                    <td class="table__leave-approved">
                      
                    </td>
                   
                    <td>
                        <!-- <php
                        $status = $leave['approval_status'];
                        $badge = match ($status) {
                            'approved' => 'success',
                            'partial'  => 'warning',
                            'rejected' => 'danger',
                            default    => 'secondary'
                        };
                        >
                        <span class="bd-badge bg-<= $badge ?>">
                            <= ucfirst($status) ?>
                        </span> -->
                    </td>
                    <!-- <td>< date('d M Y', strtotime($leave['created_at'])) ?></td> -->
                </tr>
          
        </tbody>
    </table>
</div>

<script>
    const leaveType = document.getElementById('leaveTypeSelect');
    const remaining = document.getElementById('remainingDays');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const numberDaysInput = document.getElementById('numberDays');

    leaveType.addEventListener('change', () => {
        fetch('<?= $GLOBALS['base_url'] ?>/remaining-days-self?leave_type_id=' + leaveType.value)
            .then(res => res.json())
            .then(data => {
                remaining.value = data.remaining_days;

                if (data.remaining_days === 'N/A') {
                    numberDaysInput.max = 365;
                } else {
                    numberDaysInput.max = parseInt(data.remaining_days) || '';
                }

                calculateNumberOfDays();
            });
    });

    function calculateNumberOfDays() {
        const start = new Date(startDateInput.value);
        const end = new Date(endDateInput.value);
        if (start && end && end >= start) {
            let diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
            const maxDays = parseInt(numberDaysInput.max);
            if (!isNaN(maxDays) && diffDays > maxDays) {
                diffDays = maxDays;
            }
            numberDaysInput.value = diffDays;
        } else {
            numberDaysInput.value = '';
        }
    }

    startDateInput.addEventListener('change', calculateNumberOfDays);
    endDateInput.addEventListener('change', calculateNumberOfDays);
</script>