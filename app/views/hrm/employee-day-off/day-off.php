<?php
$page_title = "Employee Day Off Manage";
$page = 'Day Off Management';
$show_breadcrumb = true;
?>

<div class="content">

    <div class="row">
        <div class="col-xxl-12">
            <div class="card__wrapper">
                
                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['success']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <form action="<?= $GLOBALS['base_url'] ?>/update-day-off" method="POST" id="dayOffForm">
                    <div class="row gy-20">
                        <div class="col-md-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label>Select Employee <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <select name="employee_id" id="employeeSelect" class="form-select form-control" required>
                                        <option value="">-- Select Employee --</option>
                                        <?php if (!empty($employees)): ?>
                                            <?php foreach ($employees as $employee): ?>
                                                <option value="<?= htmlspecialchars($employee['id']) ?>">
                                                    <?= htmlspecialchars($employee['employee_id'] . ' - ' . $employee['full_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mt-4">
                            <h5>Select Days Off</h5>
                            <hr>
                            <div class="row" id="daysContainer">
                                <?php 
                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                foreach ($days as $day): 
                                ?>
                                    <div class="col-md-3 col-sm-4 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input day-checkbox" type="checkbox" name="days_off[]" value="<?= $day ?>" id="day_<?= $day ?>" disabled>
                                            <label class="form-check-label" style="margin-left:5px" for="day_<?= $day ?>">
                                                <?= $day ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-secondary" id="saveBtn" disabled>Save Days Off</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- List of Employees with Days Off -->
            <div class="card__wrapper mt-4">
                <h4 class="mb-3">Employees Day Off List</h4>
                <div class="table__wrapper table-responsive">
                    <table class="table mb-20" id="dataTableDefualt">
                        <thead>
                            <tr class="table__title">
                                <th>Sl</th>
                                <th>Employee ID</th>
                                <th>Employee Name</th>
                                <th>Designation</th>
                                <th>Days Off</th>
                            </tr>
                        </thead>
                        <tbody class="table__body">
                            <?php if (!empty($employee_days_off)): ?>
                                <?php foreach ($employee_days_off as $index => $emp): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($emp['employee_id']) ?></td>
                                        <td><?= htmlspecialchars($emp['full_name']) ?></td>
                                        <td><?= htmlspecialchars($emp['designation_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php 
                                            // Format days off with badges
                                            if (!empty($emp['days_off'])) {
                                                $daysArray = explode(', ', $emp['days_off']);
                                                foreach ($daysArray as $d) {
                                                    echo '<span class="badge bg-secondary me-1">' . htmlspecialchars($d) . '</span>';
                                                }
                                            } else {
                                                echo '<span class="text-muted">None</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No days off assigned to any employee yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const employeeSelect = document.getElementById('employeeSelect');
    const dayCheckboxes = document.querySelectorAll('.day-checkbox');
    const saveBtn = document.getElementById('saveBtn');

    if(employeeSelect) {
        employeeSelect.addEventListener('change', function() {
            const employeeId = this.value;
            
            // Reset and disable checkboxes if no employee selected
            if (!employeeId) {
                dayCheckboxes.forEach(cb => {
                    cb.checked = false;
                    cb.disabled = true;
                });
                saveBtn.disabled = true;
                return;
            }

            // Enable UI for fetching
            dayCheckboxes.forEach(cb => {
                cb.checked = false;
                cb.disabled = true;
            });
            saveBtn.disabled = true;

            // Fetch employee days off
            fetch('<?= $GLOBALS['base_url'] ?>/get-employee-days-off/' + employeeId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error("Failed to parse JSON. Raw response:", text);
                            throw new Error("Invalid JSON returned from server.");
                        }
                    });
                })
                .then(data => {
                    if (data.status === 'success') {
                        // Enable checkboxes and button
                        dayCheckboxes.forEach(cb => cb.disabled = false);
                        saveBtn.disabled = false;

                        // Check the boxes for the days the employee has off
                        if (data.days_off && data.days_off.length > 0) {
                            data.days_off.forEach(day => {
                                const cb = document.getElementById('day_' + day);
                                if (cb) cb.checked = true;
                            });
                        }
                    } else {
                        alert('Failed to load employee data: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error fetching days off:', error);
                    alert('An error occurred while loading days off: ' + error.message);
                });
        });
    }
});
</script>
