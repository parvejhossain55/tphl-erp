<?php
$page = 'Show Attendance';
// print_r($attendanceData);
?>

<h4>Attendance for <?= $attendanceData[0]['employee_name'] ?? '-' ?> - <?= date('F Y', strtotime($selectedMonth . '-01')) ?></h4>

<!-- Filter form: only year + month -->
<form method="GET" class="mb-20 row g-3 align-items-end">
    <div class="col-xxl-3 col-lg-4 col-md-6 col-12">
        <label class="form-label">Month</label>
        <input type="month" name="month" class="form-control" value="<?= $selectedMonth ?>">
    </div>
    <div class="col-xxl-3 col-lg-4 col-md-6 col-12 d-flex align-items-end">
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-eye me-2"></i> Show
        </button>
        <a href="<?= $GLOBALS['base_url'] ?>/attendance-list"
            class="btn btn-outline-secondary px-4 ms-sm-auto">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</form>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Punch In</th>
            <th>Punch Out</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $serial = 1;
        $selectedMonth = $selectedMonth ?? date('Y-m');
        $daysInMonth   = date('t', strtotime($selectedMonth . '-01'));

        for ($d = 1; $d <= $daysInMonth; $d++):
            $date = date('d F, Y', strtotime("$selectedMonth-$d"));
            $record = array_filter($attendanceData, fn($a) => $a['attendance_date'] == $date);
            $record = $record ? array_values($record)[0] : null;

            $punchIn  = $record ? formatPunchTime($record['punch_in_time']) : '-';
            $punchOut = $record ? formatPunchTime($record['punch_out_time']) : '-';
            $status   = $record ? $record['today_status'] : '-';
        ?>
            <tr>
                <td><?= $serial++ ?></td>
                <td><?= $date ?></td>
                <td><?= $punchIn ?></td>
                <td><?= $punchOut ?></td>
                <td><?= $status ?></td>
            </tr>
        <?php endfor; ?>

    </tbody>
</table>