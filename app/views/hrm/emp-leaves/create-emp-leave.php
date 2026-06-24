<?php
$page = 'Create leave'
?>
<div class="row">
    <div class="col-12">
        <div class="card__wrapper p-4">
            <h3 class="mb-4">Employee Leave Request</h3>

            <form id="leaveRequestForm" method="POST" action="<?= $GLOBALS['base_url'] ?>/store-employee-leave">

                <div class="row ">
                    <!-- Employee Select -->
                    <div class="col-xxl-6 col-md-6 col-12 mb-2">
                        <div class="form__input-title">

                            <label for="employeeSelect" class="form-label">Employee <span class="text-danger">*</span></label>
                        </div>
                        <select id="employeeSelect" name="employee_id" class="form-select select2" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= (int)$emp['id'] ?>">
                                    <?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['employee_id']) ?>)
                                </option>

                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Leave Type Select -->
                    <div class="col-xxl-6 col-md-6 col-12 mb-2">
                        <div class="form__input-title">
                            <label for="leaveTypeSelect" class="form-label">Leave Type <span class="text-danger">*</span></label>
                        </div>
                        <select id="leaveTypeSelect" name="leave_type_id" class="form-select" required>
                            <option value="">Select Leave Type</option>
                            <?php foreach ($leaveTypes as $lt): ?>
                                <option value="<?= $lt['id'] ?>" data-total-days="<?= $lt['total_days'] ?>">
                                    <?= $lt['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Start & End Date -->
                    <div class="col-xxl-12 col-12 row mb-2">
                        <div class="col-md-6 col-12">
                            <div class="form__input-title">

                                <label for="startDate" class="form-label">Start Date <span class="text-danger">*</span></label>
                            </div>
                            <input type="date" id="startDate" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <div class="form__input-title">

                                <label for="endDate" class="form-label">End Date <span class="text-danger">*</span></label>
                            </div>
                            <input type="date" id="endDate" name="end_date" class="form-control" required>
                        </div>
                    </div>

                    <!-- Remaining Days & Number of Days -->
                    <div class="col-xxl-6 col-md-6 col-12 mb-2">
                        <div class="form__input-title">

                            <label class="form-label">Remaining Days</label>
                        </div>
                        <input type="text" id="remainingDays" class="form-control" readonly>
                    </div>

                    <div class="col-xxl-6 col-md-6 col-12">
                        <div class="form__input-title">

                            <label for="numberDays" class="form-label">Number of Days</label>
                        </div>
                        <input type="number" id="numberDays" name="number_days" class="form-control" readonly>
                    </div>

                    <!-- Reason -->
                    <div class="col-12 ">
                        <div class="form__input-title">

                            <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                        </div>
                        <textarea id="reason" name="reason" class="form-control" rows="3" required></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="col-12 text-center mt-3">
                        <button type="submit" class="btn btn-primary px-5">Submit</button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const employeeSelect = document.getElementById('employeeSelect');
        const leaveTypeSelect = document.getElementById('leaveTypeSelect');
        const remainingDaysInput = document.getElementById('remainingDays');
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');
        const numberDaysInput = document.getElementById('numberDays');

        //   leaveTypeSelect.addEventListener('change', function() {
        //         alert("Leave type changed! Selected ID: " + this.value);
        //     });

        function fetchRemainingDays() {
            const employeeId = employeeSelect.value;
            const leaveTypeId = leaveTypeSelect.value;

            if (!employeeId || !leaveTypeId) {
                remainingDaysInput.value = '';
                numberDaysInput.max = '';
                return;
            }

            fetch('<?= $GLOBALS['base_url'] ?>/remaining-days?employee_id=' + employeeId + '&leave_type_id=' + leaveTypeId)
                .then(res => res.json())
                .then(data => {
                    remainingDaysInput.value = data.remaining_days;
                    if (data.remaining_days === 'N/A') {
                        numberDaysInput.max = 365;
                    } else {
                        numberDaysInput.max = parseInt(data.remaining_days);
                    }
                })
                .catch(err => console.error('Error fetching remaining days:', err));
        }


        employeeSelect.addEventListener('change', fetchRemainingDays);
        leaveTypeSelect.addEventListener('change', fetchRemainingDays);

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
    });
</script>