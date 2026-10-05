<?php
$page_title = "Employee Day Off Manage (Test)";
$page = 'Day Off Management (Test)';
$show_breadcrumb = true;
?>

<div class="content">

    <!-- Header Section -->
    <div class="row align-items-center mb-20">
        <div class="col-12">
            <h4 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-calendar-xmark text-primary"></i>
                Day Off Management
                <span class="badge text-white fs-12 px-2 py-1 rounded-pill" style="background: #8b5cf6;">Test</span>
            </h4>
            <p class="text-muted fs-13 mb-0">Manage weekly day-offs and custom roster holidays for employees (Friday is company default)</p>
        </div>
    </div>

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
                                    <select name="employee_id" id="employeeSelect" class="form-select form-control select2" required>
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
                                                echo '<span class="badge bg-light text-muted border">None</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php ob_start(); ?>
(function() {
    function initDayOff() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.select2) {
            setTimeout(initDayOff, 50);
            return;
        }

        var $ = jQuery;
        var $employeeSelect = $('#employeeSelect');
        var $dayCheckboxes = $('.day-checkbox');
        var $saveBtn = $('#saveBtn');

        function loadEmployeeDaysOff(employeeId) {
            if (!employeeId) {
                $dayCheckboxes.prop('checked', false).prop('disabled', true);
                $saveBtn.prop('disabled', true).removeClass('btn-primary').addClass('btn-secondary');
                return;
            }

            // Immediately enable checkboxes and save button
            $dayCheckboxes.prop('checked', false).prop('disabled', false);
            $saveBtn.prop('disabled', false).removeClass('btn-secondary').addClass('btn-primary');

            // Fetch existing days off via AJAX
            $.ajax({
                url: '<?= $GLOBALS['base_url'] ?>/get-employee-days-off/' + encodeURIComponent(employeeId),
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data && data.status === 'success') {
                        if (data.days_off && data.days_off.length > 0) {
                            data.days_off.forEach(function(day) {
                                $('#day_' + day).prop('checked', true);
                            });
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.warn('Could not fetch existing days off:', error);
                }
            });
        }

        // Listen for Select2 change & native select change
        $(document).on('change select2:select select2:clear', '#employeeSelect', function() {
            loadEmployeeDaysOff($(this).val());
        });

        // Automatically trigger for already selected employee
        var currentVal = $employeeSelect.val();
        if (currentVal) {
            loadEmployeeDaysOff(currentVal);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDayOff);
    } else {
        initDayOff();
    }
})();
<?php $page_script = ob_get_clean(); ?>
