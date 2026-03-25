<div class="row">
    <div class="card__wrapper">

        <form method="POST" action="<?= $GLOBALS['base_url'] ?>/store-self-mobile-punch">
            <div class="row gy-20">
                <!-- Today Status -->
                <div class="col-lg-6 col-6">
                    <label>Today Employee Status <span>*</span></label>
                    <select class="form-control" name="today_status" required>
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

                <!-- Date -->
                <div class="col-lg-6 col-6">
                    <label>Date <span>*</span></label>
                    <input class="form-control" name="attendance_date" type="date" required>
                </div>

                <!-- Punch In -->
                <div class="col-lg-6 col-6">
                    <label>Punched In</label>
                    <input class="form-control" name="punch_in_time" type="time">
                </div>

                <!-- Punch Out -->
                <div class="col-lg-6 col-6">
                    <label>Punched Out</label>
                    <input class="form-control" name="punch_out_time" type="time">
                </div>

                <!-- Submit -->
                <div class="col-12 text-center">
                    <button class="btn btn-primary">Submit</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/attendance-list" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="card__wrapper p-4">
    <h4 class="mb-3">My Monthly Punch Report</h4>

    <table class="table table-bordered table-striped">
        <tr class="table__title">
            <th>Data</th>
            <th>Punch In </th>
            <th>Punch Out</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>



            <?php if (!empty($myAttendances)): ?>
                <?php foreach ($myAttendances as $attendance): ?>
                    <tr>
                        <td><?= htmlspecialchars($attendance['attendance_date']) ?></td>
                        <td>
                            <?php
                            if (!empty($attendance['punch_in_time'])) {
                                echo date("h:i A", strtotime($attendance['punch_in_time']));
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($attendance['punch_out_time'])) {
                                echo date("h:i A", strtotime($attendance['punch_out_time']));
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td><?= htmlspecialchars($attendance['today_status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">No attendance found for <?= htmlspecialchars($selectedDate) ?></td>
                </tr>
            <?php endif; ?>

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