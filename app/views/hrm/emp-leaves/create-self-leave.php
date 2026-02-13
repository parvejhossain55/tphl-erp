<div class="row">
    <div class="card__wrapper">

        <form method="POST" action="<?= $GLOBALS['base_url'] ?>/store-employee-leave">

            <!-- Leave Type -->
            <div class="mb-2">
                <label>Leave Type *</label>
                <select id="leaveTypeSelect" name="leave_type_id" class="form-select" required>
                    <option value="">Select Leave Type</option>
                    <?php foreach ($leaveTypes as $lt): ?>
                        <option value="<?= $lt['id'] ?>">
                            <?= $lt['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Dates -->
            <div class="row mb-2">
                <div class="col">
                    <label>Start Date *</label>
                    <input type="date" id="startDate" name="start_date" class="form-control" required>
                </div>
                <div class="col">
                    <label>End Date *</label>
                    <input type="date" id="endDate" name="end_date" class="form-control" required>
                </div>
            </div>

            <!-- Remaining -->
            <div class="row mb-2">
                <div class="col">
                    <label>Remaining Days</label>
                    <input type="text" id="remainingDays" class="form-control" readonly>
                </div>
                <div class="col">
                    <label>Number of Days</label>
                    <input type="number" id="numberDays" name="number_days" class="form-control" readonly>
                </div>
            </div>

            <div class="mb-2">
                <label>Reason *</label>
                <textarea name="reason" class="form-control" required></textarea>
            </div>

            <button class="btn btn-primary w-100">Submit</button>
        </form>
    </div>
</div>
<div class="card__wrapper p-4">
    <h4 class="mb-3">My Leave Requests</h4>

    <table class="table table-bordered table-striped">
        <tr class="table__title">
            <th>Leave Type</th>
            <th>Leave Duration</th>
            <th>Days</th>
            <th>Approved Days</th>
            <th>Remaining Leave</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Applied At</th>
        </tr>
        </thead>
        <tbody>

            <?php if (empty($leaves)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted">
                        No leave requests found
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($leaves as $i => $leave):
                $start = new DateTime($leave['start_date']);
                $end = new DateTime($leave['end_date']);
                $days = $start->diff($end)->days + 1;
                $remaining_days = $leave['total_days'] - $leave['approved_days'];
            ?>


                <tr>
                    <td><?= htmlspecialchars($leave['leave_type_name']) ?></td>

                    <td class="table__leave-duration"><?= formatDate($leave['start_date']) ?> - <?= formatDate($leave['end_date']) ?></td>
                    <td class="table__leave-days"><?= $days ?></td>
                    <td class="table__leave-approved">
                        <?= ($leave['approved_days'] ?? 0) > 0 ? $leave['approved_days'] : 'Not Approved' ?>
                    </td>
                    <td><?= $remaining_days ?? 0 ?></td>
                    <td class="table__leave-reason">
                        <?= htmlspecialchars($leave['reason'] ?? 'N/A') ?>
                    </td>
                    <td>
                        <?php
                        $status = $leave['approval_status'];
                        $badge = match ($status) {
                            'approved' => 'success',
                            'partial'  => 'warning',
                            'rejected' => 'danger',
                            default    => 'secondary'
                        };
                        ?>
                        <span class="bd-badge bg-<?= $badge ?>">
                            <?= ucfirst($status) ?>
                        </span>
                    </td>
                    <td><?= date('d M Y', strtotime($leave['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
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

            if(data.remaining_days === 'N/A'){
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