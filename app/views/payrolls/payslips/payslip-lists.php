<?php
$page_title = "Payroll Lists";
$page = 'Payroll List';
$show_breadcrumb = true;
?>
<div class="d-none flex-wrap justify-content-between align-items-center mb-4">

    <h4 class="mb-2 mb-md-0">
        Payroll Manage
    </h4>

    <div class="d-flex gap-2">
        <a href="<?= $GLOBALS['base_url'] ?>/import-payslips" class="btn btn-info"> <i class="fa-light fa-upload me-1"></i> Import Payslips (CSV)</a>
        <a href="<?= $GLOBALS['base_url'] ?>/create-payslip" class="btn btn-primary"> <i class="fa-light fa-plus me-1"></i> Create Payslip</a>
    </div>

</div>



<form action="" method="get">
    <div class="row mb-3 align-items-end">

        <?php
        $currentYear  = date('Y');
        $currentMonth = date('m');

        // Controller theke pass kora selected value, fallback to current month/year
        $selectedYear  = $selectedYear ?? $currentYear;
        $selectedMonth = $selectedMonth ?? $currentMonth;

        $startYear = $currentYear - 10;

        $months = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        ];
        ?>

        <!-- Year Select -->
        <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-12 animate__animated animate__fadeIn">
            <div class="card__wrapper h-100">
                <div class="from__input-box">
                    <select name="year" class="form-control">
                        <option value="">Year Select</option>
                        <?php for ($year = $currentYear; $year >= $startYear; $year--): ?>
                            <option value="<?= $year ?>" <?= ($selectedYear == $year) ? 'selected' : '' ?>>
                                <?= $year ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Month Select -->
        <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-12 animate__animated animate__fadeIn">
            <div class="card__wrapper h-100">
                <div class="from__input-box">
                    <select name="month" class="form-control">
                        <option value="">Month Select</option>
                        <?php foreach ($months as $id => $name): ?>
                            <option value="<?= $id ?>" <?= ($selectedMonth == $id) ? 'selected' : '' ?>>
                                <?= $name ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Status Select -->
        <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-12 animate__animated animate__fadeIn">
            <div class="card__wrapper h-100">
                <div class="from__input-box">
                    <select name="status" class="form-control">
                        <option value="">Status Select</option>
                        <option value="paid" <?= (isset($selectedStatus) && $selectedStatus == 'paid') ? 'selected' : '' ?>>Paid</option>
                        <option value="unpaid" <?= (isset($selectedStatus) && $selectedStatus == 'unpaid') ? 'selected' : '' ?>>Unpaid</option>
                        <option value="draft" <?= (isset($selectedStatus) && $selectedStatus == 'draft') ? 'selected' : '' ?>>Draft / Certificate</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Payroll Project Select -->
        <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-12 animate__animated animate__fadeIn">
            <div class="card__wrapper h-100">
                <div class="from__input-box">
                    <select name="project_id" class="form-control">
                        <option value="">All Projects</option>
                        <?php if (!empty($payroll_projects)): ?>
                            <?php foreach ($payroll_projects as $proj): ?>
                                <option value="<?= htmlspecialchars($proj['id']) ?>"
                                    <?= (isset($selectedProjectId) && $selectedProjectId == $proj['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($proj['name']) ?>
                                    <?= !empty($proj['date']) ? '(' . date('M Y', strtotime($proj['date'])) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-12 animate__animated animate__fadeIn mt-3">
            <div class="card__wrapper ">
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <button type="submit" class="btn btn-secondary w-100 px-0">
                        Search
                    </button>
                    <a href="<?= $GLOBALS['base_url'] ?>/payslip-lists" class="btn btn-success w-100 px-0">
                        Reset
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/import-payslips" class="btn btn-info w-100 px-0">
                        Import
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/create-payslip" class="btn btn-primary w-100 px-0">
                        Create
                    </a>
                </div>
            </div>
        </div>


    </div>


</form>



<div class="row gy-4 mb-4 animate__animated animate__fadeIn">
    <!-- Card 1: Total ERP Employees -->
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6 col-12">
        <a href="<?= $GLOBALS['base_url'] ?>/payslip-lists?year=<?= $selectedYear ?>&month=<?= $selectedMonth ?>&status=" class="text-decoration-none card-hover-effect">
            <div class="card__wrapper h-100 border-start border-4 border-secondary shadow-sm">
                <div class="d-flex align-items-center gap-sm p-2">
                    <div class="card__icon" style="background: rgba(108, 117, 125, 0.1); color: #6c757d; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <span><i class="fa-solid fa-server fs-4"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-5 text-secondary fw-semibold" style="font-size: 0.85rem;">Total ERP Employees</h6>
                        <div class="d-flex flex-column align-items-start">
                            <h3 class="card__title mb-0 fw-bold" style="font-size: 1.35rem; color: #2c3e50;"><?= $totalEmployees ?> <span style="font-size: 0.9rem; font-weight: normal;" class="text-muted">Staff</span></h3>
                            <span class="fs-12 text-muted mt-1"><i class="fa-solid fa-database me-1"></i>ERP Database</span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 2: Total Tipsoi Employees -->
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6 col-12">
        <div class="card__wrapper h-100 border-start border-4 border-info shadow-sm">
            <div class="d-flex align-items-center gap-sm p-2">
                <div class="card__icon" style="background: rgba(23, 162, 184, 0.1); color: #17a2b8; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <span><i class="fa-solid fa-users-viewfinder fs-4"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-5 text-info fw-semibold" style="font-size: 0.85rem;">Total Tipsoi Employees</h6>
                    <div class="d-flex flex-column align-items-start">
                        <h3 class="card__title mb-0 fw-bold" style="font-size: 1.35rem; color: #2c3e50;"><span id="tipsoiEmployeesCount">0</span> <span style="font-size: 0.9rem; font-weight: normal;" class="text-muted">Staff</span></h3>
                        <span class="fs-12 text-muted mt-1"><i class="fa-solid fa-network-wired me-1"></i>Active in Tipsoi</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Not in ERP (Tipsoi) -->
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6 col-12">
        <a href="javascript:void(0);" id="btnViewUnregistered" class="text-decoration-none card-hover-effect">
            <div class="card__wrapper h-100 border-start border-4 border-danger shadow-sm">
                <div class="d-flex align-items-center gap-sm p-2">
                    <div class="card__icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <span><i class="fa-solid fa-user-slash fs-4 animate__animated animate__pulse animate__infinite"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-5 text-danger fw-semibold" style="font-size: 0.85rem;">Not in ERP (Tipsoi)</h6>
                        <div class="d-flex flex-column align-items-start">
                            <h3 class="card__title mb-0 fw-bold" style="font-size: 1.35rem; color: #2c3e50;"><span id="unregisteredCount">0</span> <span style="font-size: 0.9rem; font-weight: normal;" class="text-muted">Employees</span></h3>
                            <span class="fs-12 text-danger mt-1 fw-bold"><i class="fa-regular fa-eye me-1"></i>Click to view list</span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 4: Total Paid -->
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6 col-12">
        <a href="<?= $GLOBALS['base_url'] ?>/payslip-lists?year=<?= $selectedYear ?>&month=<?= $selectedMonth ?>&status=paid" class="text-decoration-none card-hover-effect">
            <div class="card__wrapper h-100 border-start border-4 border-success shadow-sm">
                <div class="d-flex align-items-center gap-sm p-2">
                    <div class="card__icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <span><i class="fa-light fa-badge-check fs-4"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-5 text-success fw-semibold" style="font-size: 0.85rem;">Total Paid</h6>
                        <div class="d-flex flex-column align-items-start">
                            <h3 class="card__title mb-0 fw-bold" style="font-size: 1.35rem; color: #2c3e50;"><?= $paidCount ?> <span style="font-size: 0.9rem; font-weight: normal;" class="text-muted">Paid Slips</span></h3>
                            <span class="fs-12 text-success mt-1 fw-semibold"><i class="fa-solid fa-circle-check me-1"></i><?= number_format($paidSalarySum, 2) ?> TK</span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 5: Total Unpaid -->
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6 col-12">
        <a href="<?= $GLOBALS['base_url'] ?>/payslip-lists?year=<?= $selectedYear ?>&month=<?= $selectedMonth ?>&status=unpaid" class="text-decoration-none card-hover-effect">
            <div class="card__wrapper h-100 border-start border-4 border-warning shadow-sm">
                <div class="d-flex align-items-center gap-sm p-2">
                    <div class="card__icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <span><i class="fa-sharp fa-regular fa-user fs-4"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-5 text-warning fw-semibold" style="font-size: 0.85rem;">Total Unpaid</h6>
                        <div class="d-flex flex-column align-items-start">
                            <h3 class="card__title mb-0 fw-bold" style="font-size: 1.35rem; color: #2c3e50;"><?= $pendingCount ?> <span style="font-size: 0.9rem; font-weight: normal;" class="text-muted">Pending Slips</span></h3>
                            <span class="fs-12 text-warning mt-1 fw-semibold"><i class="fa-solid fa-clock me-1"></i><?= number_format($unpaidSalarySum, 2) ?> TK</span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 6: Total Paid Amount -->
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6 col-12">
        <a href="<?= $GLOBALS['base_url'] ?>/payslip-lists?year=<?= $selectedYear ?>&month=<?= $selectedMonth ?>&status=paid" class="text-decoration-none card-hover-effect">
            <div class="card__wrapper h-100 border-start border-4 border-primary shadow-sm">
                <div class="d-flex align-items-center gap-sm p-2">
                    <div class="card__icon" style="background: rgba(0, 123, 255, 0.1); color: #007bff; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <span><i class="fa-light fa-money-bill-wave fs-4"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-5 text-primary fw-semibold" style="font-size: 0.85rem;">Total Paid Amount</h6>
                        <div class="d-flex flex-column align-items-start">
                            <h3 class="card__title mb-0 fw-bold" style="font-size: 1.35rem; color: #007bff;"><?= number_format($paidSalarySum, 2) ?> TK</h3>
                            <span class="fs-12 text-muted mt-1"><i class="fa-solid fa-vault me-1"></i>Paid Funds</span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

    <div class="col-xxl-12 animate__animated animate__fadeIn">
        <div class="card__wrapper">
            <div class="d-flex justify-content-between align-items-center mb-15 flex-wrap gap-2">
                <h5 class="fw-bold m-0 text-dark" style="font-size: 1rem;">Salary Slips</h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="filterLwpOnly">
                    <label class="form-check-label fw-bold text-secondary" style="font-size: 0.85rem;" for="filterLwpOnly">
                        <i class="fa-solid fa-filter me-1 text-danger animate__animated animate__pulse animate__infinite"></i> Show LWP Employees Only
                    </label>
                </div>
            </div>
            <div id="lwpDebugBanner" class="alert alert-warning py-2 px-3 mb-3 d-none fs-12 fw-bold" style="font-family: monospace;"></div>
            <!-- Accounts Payroll Disbursement Action Bar -->
            <div class="card p-3 mb-3 border-success bg-light animate__animated animate__fadeIn">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-light fa-building-columns text-success fs-4"></i>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">Accounts Payroll Disbursement & Journal Posting</h6>
                            <small class="text-muted">Select un-disbursed payslips below, choose payment account & disburse in 1 click.</small>
                        </div>
                    </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <label class="form-label mb-0 fw-bold fs-13">Paying Bank/Cash:</label>
                            <select id="disburse_bank_account_id" class="form-select form-select-sm" style="min-width: 220px;">
                                <option value="0">Petty Cash / Cash Account</option>
                                <?php foreach ($companyBanks as $bank): ?>
                                    <option value="<?= $bank['id'] ?>">
                                        <?= htmlspecialchars($bank['bank_name']) ?> - <?= htmlspecialchars($bank['account_number']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-success btn-sm px-3 fw-bold" onclick="disburseSelectedPayroll()">
                                <i class="fa-light fa-money-check-dollar me-1"></i> Disburse Selected & Post Journal
                            </button>
                            <button type="button" class="btn btn-danger btn-sm px-3 fw-bold" onclick="bulkDeleteSelected()">
                                <i class="fa-light fa-trash-can me-1"></i> Bulk Delete
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table__wrapper table-responsive">
                    <table class="table hover mb-20" id="payslipListTable">
                        <thead>
                        <tr class="table__title">
                            <th><input type="checkbox" id="selectAllPayslips" onclick="toggleSelectAllPayslips(this)"></th>
                            <th>SL</th>
                            <th class="th_1">Employee</th>
                            <th>Designation</th>
                            <th>Department</th>
                            <th>Email</th>
                            <th>Joining Date</th>
                            <th>Gross Salary (Monthly)</th>
                            <th>LWP Days</th>
                            <th>Approval Time</th>
                            <th>Status</th>
                            <th class="">Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php $sl = 1; ?>
                        <?php foreach ($payslips as $payslip): ?>
                            <?php if ($payslip['status'] == 'draft') continue; ?>
                            <tr>
                                <td><input type="checkbox" class="payslip-checkbox" value="<?= $payslip['id'] ?>" <?= $payslip['status'] == 'paid' ? 'disabled' : '' ?>></td>
                                <td><?= $sl++ ?></td>
                                <td class="td_1">
                                    <span class="table-avatar">
                                        <a class="employee__avatar mr-5" href="<?= $GLOBALS['base_url'] ?>/employee-profile/<?= $payslip['employee_id'] ?>"><img
                                                class="img-48 border-circle"
                                                src="<?= getImage('employees', $payslip['photo']) ?>"
                                                 alt="User Image"><strong class="ps-2"><?= htmlspecialchars($payslip['employee_office_id'] ?? '') ?></strong></a>
                                         <a href="<?= $GLOBALS['base_url'] ?>/employee-profile/<?= $payslip['employee_id'] ?>"><?= $payslip['employee_name'] ?></a>
                                    </span>
                                </td>
                                <td class="table__employee-position"><?= $payslip['desgination_name'] ?></td>
                                <td class="table__employee-department"><?= htmlspecialchars($payslip['department_name'] ?? 'N/A') ?></td>
                                <td class="table__employee-email">
                                    <a href="mailto:<?= htmlspecialchars($payslip['email']) ?>">
                                        <?= htmlspecialchars($payslip['email']) ?>
                                    </a>
                                </td>


                                <td class="table__joining-date"><?= $payslip['joining_date'] ?></td>

                                <td class="table__salary"><?= $payslip['net_salary'] ?></td>
                                <?php
                                $lwpAmount = (float)($payslip['lwp_amount'] ?? 0);
                                $grossSalary = (float)($payslip['emp_gross_salary'] ?? 0);
                                $phpLwpDays = 0;
                                if ($lwpAmount > 0 && $grossSalary > 0) {
                                    $perDay = $grossSalary / 30;
                                    $phpLwpDays = (int)round($lwpAmount / $perDay);
                                }
                                ?>
                                <td class="table__lwp-days text-center" 
                                    data-emp-office-id="<?= htmlspecialchars($payslip['employee_office_id'] ?? '') ?>" 
                                    data-emp-id="<?= $payslip['employee_id'] ?>" 
                                    data-emp-name="<?= htmlspecialchars($payslip['employee_name']) ?>"
                                    data-php-lwp-days="<?= $phpLwpDays ?>">
                                    <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                                </td>
                                <td class="table__approval-time">
                                    <?= ($payslip['status'] == 'draft') ? 'N/A (Draft)' : (!empty($payslip['approval_time']) ? date('d M Y H:i', strtotime($payslip['approval_time'])) : 'Not Approved') ?>
                                </td>
                                 <td class="table__delivery ">
                                     <?php if ($payslip['status'] == 'draft'): ?>
                                         <span class="bd-badge bg-secondary">Draft / Certificate</span>
                                     <?php elseif ($payslip['status'] == 'paid'): ?>
                                         <span class="bd-badge bg-success">Paid</span>
                                     <?php elseif ($payslip['status'] == 'approved'): ?>
                                         <span class="bd-badge bg-primary">Approved</span>
                                     <?php elseif ($payslip['status'] == 'reject'): ?>
                                         <span class="bd-badge bg-danger">Reject</span>
                                     <?php elseif ($payslip['status'] == 'unpaid'): ?>
                                         <span class="bd-badge bg-warning text-dark">Unpaid</span>
                                     <?php else: ?>
                                         <span class="status-badge status-pending">Pending</span>
                                     <?php endif; ?>
                                 </td>



                                 <td class="able__icon-box">
                                     <div class="d-flex align-items-center justify-content-start gap-10">
                                         <?php if (empty($payslip['id'])): ?>
                                             <a href="<?= $GLOBALS['base_url'] ?>/create-payslip" class="btn btn-info btn-sm">
                                                 <i class="fa-light fa-plus me-1"></i> Create Payslip
                                             </a>
                                         <?php else: ?>
                                             <?php if ($payslip['status'] == 'draft'): ?>
                                                 <span class="badge bg-secondary-subtle text-secondary border py-2 px-2" style="font-size: 11px;">Draft Certificate</span>
                                             <?php elseif ($payslip['status'] != 'paid'): ?>
                                                 <button
                                                     class="btn btn-warning btn-sm payPayslipBtn"
                                                     data-id="<?= $payslip['id'] ?>"
                                                     data-earning="<?= $payslip['total_earning'] ?>"
                                                     data-deduction="<?= $payslip['total_deduction'] ?>"
                                                     data-net="<?= $payslip['net_salary'] ?>">
                                                     Payment
                                                 </button>
                                             <?php else: ?>
                                                 <button
                                                     class="btn btn-primary btn-sm desabled">
                                                     Completed
                                                 </button>
                                             <?php endif; ?>

                                             <a href="<?= $GLOBALS['base_url'] ?>/show-payslip/<?= $payslip['id'] ?>" class="table__icon download" title="View Payslip">
                                                 <i class="fa-regular fa-eye"></i>
                                             </a>
                                             <a href="<?= $GLOBALS['base_url'] ?>/show-payslip/<?= $payslip['id'] ?>?print=1" class="table__icon download" title="Print Payslip">
                                                 <i class="fas fa-print"></i>
                                             </a>
                                             <a href="<?= $GLOBALS['base_url'] ?>/delete-payslip/<?= $payslip['id'] ?>" onclick="return confirm('Are you sure you want to delete this payslip?')" class="table__icon text-danger" title="Delete Payslip">
                                                 <i class="fa-regular fa-trash-can"></i>
                                             </a>
                                         <?php endif; ?>
                                     </div>
                                 </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="col-xxl-12 animate__animated animate__fadeIn mt-4">
    <div class="card__wrapper">
        <div class="d-flex justify-content-between align-items-center mb-15 flex-wrap gap-2">
            <h5 class="fw-bold m-0 text-dark" style="font-size: 1rem;">Draft Salary Certificates</h5>
        </div>
        <div class="table__wrapper table-responsive">
            <table class="table hover mb-20" id="draftPayslipListTable">
                <thead>
                    <tr class="table__title">
                        <th><input type="checkbox" id="selectAllDraftPayslips" onclick="toggleSelectAllDraftPayslips(this)"></th>
                        <th>SL</th>
                        <th class="th_1">Employee</th>
                        <th>Designation</th>
                        <th>Department</th>
                        <th>Email</th>
                        <th>Joining Date</th>
                        <th>Gross Salary (Monthly)</th>
                        <th>Status</th>
                        <th class="">Action</th>
                    </tr>
                </thead>
                <tbody class="table__body">
                    <?php $sl2 = 1; ?>
                    <?php foreach ($payslips as $payslip): ?>
                        <?php if ($payslip['status'] != 'draft') continue; ?>
                        <tr>
                            <td><input type="checkbox" class="draft-payslip-checkbox payslip-checkbox" value="<?= $payslip['id'] ?>"></td>
                            <td><?= $sl2++ ?></td>
                            <td class="td_1">
                                <span class="table-avatar">
                                    <a class="employee__avatar mr-5" href="<?= $GLOBALS['base_url'] ?>/employee-profile/<?= $payslip['employee_id'] ?>"><img class="img-48 border-circle" src="<?= getImage('employees', $payslip['photo']) ?>" alt="User Image"><strong class="ps-2"><?= htmlspecialchars($payslip['employee_office_id'] ?? '') ?></strong></a>
                                    <a href="<?= $GLOBALS['base_url'] ?>/employee-profile/<?= $payslip['employee_id'] ?>"><?= $payslip['employee_name'] ?></a>
                                </span>
                            </td>
                            <td class="table__employee-position"><?= $payslip['desgination_name'] ?></td>
                            <td class="table__employee-department"><?= htmlspecialchars($payslip['department_name'] ?? 'N/A') ?></td>
                            <td class="table__employee-email"><a href="mailto:<?= htmlspecialchars($payslip['email']) ?>"><?= htmlspecialchars($payslip['email']) ?></a></td>
                            <td class="table__joining-date"><?= $payslip['joining_date'] ?></td>
                            <td class="table__salary"><?= $payslip['net_salary'] ?></td>
                            <td class="table__delivery"><span class="bd-badge bg-secondary">Draft / Certificate</span></td>
                            <td class="able__icon-box">
                                <div class="d-flex align-items-center justify-content-start gap-10">
                                    <span class="badge bg-secondary-subtle text-secondary border py-2 px-2" style="font-size: 11px;">Draft Certificate</span>
                                    <a href="<?= $GLOBALS['base_url'] ?>/show-payslip/<?= $payslip['id'] ?>" class="table__icon download" title="View Payslip"><i class="fa-regular fa-eye"></i></a>
                                    <a href="<?= $GLOBALS['base_url'] ?>/show-payslip/<?= $payslip['id'] ?>?print=1" class="table__icon download" title="Print Payslip"><i class="fas fa-print"></i></a>
                                    <a href="<?= $GLOBALS['base_url'] ?>/delete-payslip/<?= $payslip['id'] ?>" onclick="return confirm('Are you sure you want to delete this payslip?')" class="table__icon text-danger" title="Delete Payslip"><i class="fa-regular fa-trash-can"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
<!-- Pay Payslip Modal -->
<div id="payPayslipModal" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title">Pay Payslip</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/pay-payslip">

                    <input type="hidden" name="payslip_id" id="modalPayslipId">

                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">

                                    <!-- Total Earning -->
                                    <div class="col-md-4">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Total Earning</label>
                                            </div>
                                            <div class="form__input">
                                                <input type="text" id="modalTotalEarning" class="form-control" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Total Deduction -->
                                    <div class="col-md-4">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Total Deduction</label>
                                            </div>
                                            <div class="form__input">
                                                <input type="text" id="modalTotalDeduction" class="form-control" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Net Salary -->
                                    <div class="col-md-4">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Net Salary</label>
                                            </div>
                                            <div class="form__input">
                                                <input type="text" id="modalNetSalary" class="form-control fw-bold" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Payment Method -->
                                    <div class="col-md-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Payment Method <span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <select name="payment_method" id="paymentMethod" class="form-select" required>
                                                    <option value="">Select</option>
                                                    <option value="bank">Bank</option>
                                                    <option value="cash">Cash</option>
                                                    <option value="mobile_banking">Mobile Banking</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Company Bank -->
                                    <div class="col-md-6 d-none" id="companyBankWrapper">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Company Bank</label>
                                            </div>
                                            <div class="form__input">
                                                <select name="company_bank_id" id="companyBankSelect" class="form-select">
                                                    <option value="">Select Bank</option>
                                                    <?php foreach ($companyBanks as $bank): ?>
                                                        <option value="<?= $bank['id'] ?>"
                                                            data-bank-name="<?= $bank['bank_name'] ?>"
                                                            data-account-name="<?= $bank['account_name'] ?>"
                                                            data-account-number="<?= $bank['account_number'] ?>">
                                                            <?= $bank['bank_name'] ?> (<?= $bank['account_number'] ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <!-- Bank Details -->
                                            <div id="bankDetails" class="d-none mt-2">
                                                <p><strong>Bank Name:</strong> <span id="bankName"></span></p>
                                                <p><strong>Account Name:</strong> <span id="accountName"></span></p>
                                                <p><strong>Account Number:</strong> <span id="accountNumber"></span></p>
                                            </div>
                                        </div>
                                    </div>

                                </div> <!-- row -->
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="submit__btn text-center mt-30">
                        <button type="submit" class="btn btn-success">
                            Confirm & Pay
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>




<script>
    document.addEventListener("DOMContentLoaded", function() {

        const paymentMethod = document.getElementById('paymentMethod');
        const companyBankWrapper = document.getElementById('companyBankWrapper');
        const companyBankSelect = document.getElementById('companyBankSelect');
        const bankDetails = document.getElementById('bankDetails');
        const bankName = document.getElementById('bankName');
        const accountName = document.getElementById('accountName');
        const accountNumber = document.getElementById('accountNumber');

        // Show/hide bank section based on payment method
        paymentMethod.addEventListener('change', function() {
            if (this.value === 'bank') {
                companyBankWrapper.classList.remove('d-none');
            } else {
                companyBankWrapper.classList.add('d-none');
                bankDetails.classList.add('d-none');
            }
        });

        // Show bank details when a bank is selected
        companyBankSelect.addEventListener('change', function() {
            const selectedOption = this.selectedOptions[0];
            if (selectedOption && selectedOption.value !== "") {
                bankName.textContent = selectedOption.dataset.bankName;
                accountName.textContent = selectedOption.dataset.accountName;
                accountNumber.textContent = selectedOption.dataset.accountNumber;
                bankDetails.classList.remove('d-none');
            } else {
                bankDetails.classList.add('d-none');
            }
        });

        // Open modal and prefill payslip data
        document.querySelectorAll('.payPayslipBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('modalPayslipId').value = this.dataset.id;
                document.getElementById('modalTotalEarning').value = this.dataset.earning;
                document.getElementById('modalTotalDeduction').value = this.dataset.deduction;
                document.getElementById('modalNetSalary').value = this.dataset.net;

                // Reset payment method & bank details
                paymentMethod.value = "";
                companyBankWrapper.classList.add('d-none');
                bankDetails.classList.add('d-none');
                companyBankSelect.value = "";

                new bootstrap.Modal(document.getElementById('payPayslipModal')).show();
            });
        });

        // -------------------------------------------------------------
        // Fetch and Populate LWP Days for Selected Month
        // -------------------------------------------------------------
        const base_url = '<?= $GLOBALS['base_url'] ?>';
        const selectedYear = "<?= $selectedYear ?>";
        const selectedMonth = "<?= $selectedMonth ?>";

        const firstDay = `${selectedYear}-${String(selectedMonth).padStart(2, '0')}-01`;
        const lastDayVal = new Date(selectedYear, selectedMonth, 0).getDate();
        const lastDay = `${selectedYear}-${String(selectedMonth).padStart(2, '0')}-${String(lastDayVal).padStart(2, '0')}`;

        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        const selectedMonthName = monthNames[parseInt(selectedMonth, 10) - 1];

        // Local employees list passed from PHP
        const localEmployeesList = <?= json_encode($allLocalEmployees) ?>;

        const normalizeId = (id) => {
            const str = String(id || '').trim();
            return /^\d+$/.test(str) ? String(parseInt(str, 10)) : str;
        };

        function getDatesInRange(startDateStr, endDateStr) {
            const dates = [];
            let current = new Date(startDateStr);
            const end = new Date(endDateStr);
            while (current <= end) {
                dates.push(current.toISOString().split('T')[0]);
                current.setDate(current.getDate() + 1);
            }
            return dates;
        }

        console.log("selectedYear:", selectedYear, "selectedMonth:", selectedMonth);
        console.log("firstDay:", firstDay, "lastDay:", lastDay);

        // 1. Fetch All Tipsoi Employees to get accurate Counts & Unregistered Employees List
        fetch(`${base_url}/api/hrm/external-employees`)
            .then(res => res.json())
            .then(response => {
                if (response.success && response.data) {
                    const tipsoiEmployees = response.data.map(emp => {
                        return {
                            officeId: normalizeId(emp.employeeOfficeId || emp.employeeId),
                            employeeName: emp.employeeName || '—',
                            departmentName: emp.departmentName || 'No Department',
                            designationName: emp.designationName || emp.designation || 'No Designation'
                        };
                    });

                    // Update Tipsoi Employees Count on Card
                    const tipsoiEmployeesCountSpan = document.getElementById('tipsoiEmployeesCount');
                    if (tipsoiEmployeesCountSpan) {
                        tipsoiEmployeesCountSpan.textContent = tipsoiEmployees.length;
                    }

                    // Calculate unregistered employees (in Tipsoi but not in ERP)
                    const unregisteredEmployees = tipsoiEmployees.filter(te => {
                        const matchedLocal = localEmployeesList.find(le => normalizeId(le.employee_id) === te.officeId);
                        return !matchedLocal;
                    });

                    // Update Count on Card
                    const unregisteredCountSpan = document.getElementById('unregisteredCount');
                    if (unregisteredCountSpan) {
                        unregisteredCountSpan.textContent = unregisteredEmployees.length;
                    }

                    // Setup Unregistered Card View Click Handler
                    const btnViewUnregistered = document.getElementById('btnViewUnregistered');
                    if (btnViewUnregistered) {
                        const newBtn = btnViewUnregistered.cloneNode(true);
                        btnViewUnregistered.parentNode.replaceChild(newBtn, btnViewUnregistered);
                        newBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            if (unregisteredEmployees.length === 0) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'All Employees Registered!',
                                    text: 'All employees in Tipsoi are successfully registered in the ERP database.',
                                    confirmButtonColor: '#15a15b'
                                });
                                return;
                            }

                            const tableRows = unregisteredEmployees.map((emp, index) => `
                                <tr>
                                    <td class="text-muted fw-semibold">${index + 1}</td>
                                    <td class="fw-bold text-danger">${emp.officeId}</td>
                                    <td>${emp.employeeName}</td>
                                    <td><span class="badge bg-secondary-subtle text-secondary">${emp.departmentName}</span></td>
                                    <td><span class="badge bg-primary-subtle text-primary">${emp.designationName}</span></td>
                                </tr>
                            `).join('');

                            Swal.fire({
                                title: `<span style="font-size: 1.25rem; font-weight: 700; color: #dc3545;"><i class="fa-solid fa-user-slash me-2"></i> Not Added in ERP (Tipsoi)</span>`,
                                html: `
                                    <p class="text-muted text-start mb-3 fs-13">These employees exist in the Tipsoi attendance system but are missing from your ERP. Salary cannot be paid for them until they are added to the ERP.</p>
                                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                        <table class="table table-sm table-striped table-bordered text-start fs-12 mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>SL</th>
                                                    <th>Office ID</th>
                                                    <th>Name</th>
                                                    <th>Department</th>
                                                    <th>Designation</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${tableRows}
                                            </tbody>
                                        </table>
                                    </div>
                                `,
                                width: '700px',
                                confirmButtonColor: '#dc3545',
                                confirmButtonText: 'Close'
                            });
                        });
                    }
                }
            })
            .catch(err => console.error("Error fetching external employees: ", err));

        // 2. Fetch Leave History to Calculate LWP Days
        fetch(`${base_url}/api/hrm/leave-history?from_date=${firstDay}&to_date=${lastDay}`)
            .then(res => res.json())
            .then(response => {
                console.log("API response:", response);
                const empGroups = {};
                let flatHistoryRecords = [];

                if (response.success && response.data) {
                    response.data.forEach(emp => {
                        const rawOfficeId = emp.employeeOfficeId || emp.employeeId;
                        const dataList = emp.leaveLeaveInfo?.leaveDataList || [];
                        dataList.forEach(item => {
                            if (item.from !== null) {
                                flatHistoryRecords.push({
                                    employeeOfficeId: rawOfficeId,
                                    employeeName: emp.employeeName,
                                    leaveCategoryName: item.leaveCategoryName,
                                    from: item.from,
                                    to: item.to,
                                    approvalStatus: item.approvalStatus
                                });
                            }
                        });
                    });

                    const lwpHistory = flatHistoryRecords.filter(rec => {
                        const isLwp = String(rec.leaveCategoryName || '').toLowerCase().includes('without pay') ||
                                      String(rec.leaveCategoryName || '').toLowerCase().includes('lwp');
                        return isLwp && rec.approvalStatus === 1;
                    });

                    console.log("lwpHistory approved count:", lwpHistory.length);

                    lwpHistory.forEach(rec => {
                        const officeId = normalizeId(rec.employeeOfficeId);
                        const dates = getDatesInRange(rec.from, rec.to);
                        
                        const monthPattern = `${selectedYear}-${String(selectedMonth).padStart(2, '0')}`;
                        dates.forEach(d => {
                            if (d.startsWith(monthPattern)) {
                                if (!empGroups[officeId]) {
                                    empGroups[officeId] = {
                                        datesList: new Set(),
                                        employeeName: rec.employeeName
                                    };
                                }
                                empGroups[officeId].datesList.add(d);
                            }
                        });
                    });
                }

                console.log("empGroups constructed:", empGroups);
                const cells = document.querySelectorAll('.table__lwp-days');
                console.log("table__lwp-days cells found:", cells.length);

                const debugBanner = document.getElementById('lwpDebugBanner');
                if (debugBanner) {
                    const cellOfficeIds = Array.from(cells).map(c => c.dataset.empOfficeId || 'NULL');
                    debugBanner.innerHTML = `
                        LWP Debug: 
                        [Month: ${selectedYear}-${selectedMonth}] 
                        [API Recs: ${response.data ? response.data.length : 0}] 
                        [LWP Approved: ${flatHistoryRecords ? flatHistoryRecords.filter(r => String(r.leaveCategoryName).toLowerCase().includes('without pay') && r.approvalStatus === 1).length : 0}] 
                        [LWP Groups: ${Object.keys(empGroups).join(', ') || 'None'}] 
                        [Table Rows: ${cells.length}] 
                        [Table Office IDs: ${cellOfficeIds.slice(0, 15).join(', ')}${cellOfficeIds.length > 15 ? '...' : ''}]
                    `;
                    debugBanner.classList.remove('d-none');
                }

                cells.forEach(cell => {
                    const rawId = cell.dataset.empOfficeId || '';
                    const officeId = normalizeId(rawId);
                    const empName = cell.dataset.empName || '';
                    const parentRow = cell.closest('tr');
                    const phpLwpDays = parseInt(cell.getAttribute('data-php-lwp-days') || '0', 10);

                    console.log("Cell rawId:", rawId, "normalized officeId:", officeId, "matches group:", !!empGroups[officeId]);

                    if (empGroups[officeId]) {
                        const datesArray = Array.from(empGroups[officeId].datesList).sort();
                        const count = datesArray.length;
                        
                        parentRow.setAttribute('data-lwp-days', count);
                        parentRow.setAttribute('data-lwp-dates-json', JSON.stringify(datesArray));

                        cell.innerHTML = `
                            <div class="d-flex align-items-center justify-content-center gap-1 animate__animated animate__fadeIn">
                                <span class="badge bg-danger-subtle text-danger px-2 py-1 fw-bold border border-danger-subtle">${count} Days</span>
                                <button type="button" class="btn btn-xs btn-link p-0 text-decoration-none view-lwp-breakdown" 
                                        data-name="${empName}" 
                                        data-count="${count}"
                                        data-id="${officeId}">
                                    <i class="fa-regular fa-eye text-primary" style="font-size:0.85rem;"></i>
                                </button>
                            </div>
                        `;
                    } else {
                        if (phpLwpDays > 0) {
                            parentRow.setAttribute('data-lwp-days', phpLwpDays);
                            cell.innerHTML = `
                                <div class="d-flex align-items-center justify-content-center gap-1 animate__animated animate__fadeIn">
                                    <span class="badge bg-danger-subtle text-danger px-2 py-1 fw-bold border border-danger-subtle">${phpLwpDays} Days</span>
                                </div>
                            `;
                        } else {
                            parentRow.setAttribute('data-lwp-days', '0');
                            cell.innerHTML = `<span class="text-muted" style="font-size: 0.85rem;">—</span>`;
                        }
                    }
                });

                document.querySelectorAll('.view-lwp-breakdown').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const name = this.dataset.name;
                        const count = this.dataset.count;
                        const row = this.closest('tr');
                        const dates = JSON.parse(row.getAttribute('data-lwp-dates-json') || '[]');

                        const formattedDates = dates.map(d => {
                            const dObj = new Date(d);
                            return `<li class="mb-1 text-muted text-start" style="padding-left:15px; text-indent: -15px;"><i class="fa-regular fa-calendar-days text-danger me-2"></i><strong>${dObj.toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' })}</strong></li>`;
                        }).join('');

                        Swal.fire({
                            title: `<span style="font-size: 1.15rem; font-weight: 700; color: #333;"><i class="fa-solid fa-calendar-xmark text-danger me-2"></i> LWP Breakdown</span>`,
                            html: `
                                <div class="text-start fs-13 mt-3" style="font-family:'Inter',sans-serif; line-height: 1.6;">
                                    <p class="mb-2 text-dark"><strong>Employee Name:</strong> <span class="text-muted">${name}</span></p>
                                    <p class="mb-2 text-dark"><strong>Month:</strong> <span class="text-muted">${selectedMonthName} ${selectedYear}</span></p>
                                    <p class="mb-3 text-dark"><strong>Total LWP Days:</strong> <span class="badge bg-danger fs-12 px-2 py-1">${count} Days</span></p>
                                    <hr class="my-2">
                                    <p class="mb-2 fw-bold text-secondary">Specific Leave Dates:</p>
                                    <ul class="ps-0 mb-0" style="list-style: none;">
                                        ${formattedDates}
                                    </ul>
                                </div>
                            `,
                            confirmButtonColor: '#15a15b',
                            confirmButtonText: 'Close'
                        });
                    });
                });
            })
            .catch(err => {
                console.error("LWP Fetch Error: ", err);
                document.querySelectorAll('.table__lwp-days').forEach(cell => {
                    cell.innerHTML = `<span class="text-muted" title="Fetch error">—</span>`;
                    cell.closest('tr').setAttribute('data-lwp-days', '0');
                });
            });

        // -------------------------------------------------------------
        // LWP Only Filter switch toggle
        // -------------------------------------------------------------
        const filterLwpCheckbox = document.getElementById('filterLwpOnly');
        if (filterLwpCheckbox) {
            filterLwpCheckbox.addEventListener('change', function() {
                const checked = this.checked;
                const rows = document.querySelectorAll('#payslipListTable tbody tr');
                
                let foundMatch = false;
                rows.forEach(row => {
                    const lwpDays = parseInt(row.getAttribute('data-lwp-days') || '0', 10);
                    if (checked) {
                        if (lwpDays > 0) {
                            row.style.display = '';
                            foundMatch = true;
                        } else {
                            row.style.display = 'none';
                        }
                    } else {
                        row.style.display = '';
                        foundMatch = true;
                    }
                });

                const existingPlaceholder = document.getElementById('lwpNoRecordsRow');
                if (existingPlaceholder) {
                    existingPlaceholder.remove();
                }

                if (!foundMatch && checked) {
                    const tbody = document.querySelector('#payslipListTable tbody');
                    const colsCount = document.querySelectorAll('#payslipListTable thead tr th').length;
                    const placeholder = document.createElement('tr');
                    placeholder.id = 'lwpNoRecordsRow';
                    placeholder.innerHTML = `
                        <td colspan="${colsCount}" class="text-center py-5 text-muted animate__animated animate__fadeIn">
                            <i class="fa-solid fa-inbox fs-2 mb-2 d-block text-danger"></i>
                            No employees took LWP in ${selectedMonthName} ${selectedYear}.
                        </td>
                    `;
                    tbody.appendChild(placeholder);
                }
            });
        }

    });

    function toggleSelectAllPayslips(master) {
        document.querySelectorAll('#payslipListTable .payslip-checkbox:not(:disabled)').forEach(cb => {
            cb.checked = master.checked;
        });
    }

    function toggleSelectAllDraftPayslips(master) {
        document.querySelectorAll('#draftPayslipListTable .payslip-checkbox:not(:disabled)').forEach(cb => {
            cb.checked = master.checked;
        });
    }

    function bulkDeleteSelected() {
        const selectedIds = [];
        document.querySelectorAll('.payslip-checkbox:checked').forEach(cb => {
            selectedIds.push(cb.value);
        });

        if (selectedIds.length === 0) {
            Swal.fire('Warning', 'Please select at least one payslip to delete.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Delete Selected Payslips?',
            html: `Are you sure you want to delete <b>${selectedIds.length}</b> selected payslips?<br><br><small class="text-danger">This action cannot be undone.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc3545',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch('<?= $GLOBALS['base_url'] ?>/bulk-delete-payslips', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        payslip_ids: selectedIds
                    })
                })
                .then(response => {
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return response.json().then(data => {
                            if (!response.ok || !data.success) {
                                throw new Error(data.message || 'Failed to delete payslips.');
                            }
                            return data;
                        });
                    } else {
                        return response.text().then(text => {
                            console.error("Server non-JSON response:", text);
                            throw new Error('Server returned an invalid format. Check console.');
                        });
                    }
                })
                .catch(error => {
                    Swal.showValidationMessage(`${error}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Deleted!',
                    text: result.value.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            }
        });
    }

    function disburseSelectedPayroll() {
        const selectedIds = [];
        document.querySelectorAll('#payslipListTable .payslip-checkbox:checked').forEach(cb => {
            selectedIds.push(cb.value);
        });

        if (selectedIds.length === 0) {
            Swal.fire('Warning', 'Please select at least one payslip to disburse.', 'warning');
            return;
        }

        const bankSelect = document.getElementById('disburse_bank_account_id');
        const bankId = bankSelect ? bankSelect.value : 0;
        const bankText = bankSelect ? bankSelect.options[bankSelect.selectedIndex].text : 'Petty Cash / Cash Account';

        Swal.fire({
            title: 'Disburse Payroll?',
            html: `Are you sure you want to disburse <b>${selectedIds.length}</b> selected payslips via <b>${bankText}</b>?<br><br><small class=\"text-muted\">This will mark the payslips as PAID and automatically post 1 Batch Accounting Journal Entry in General Journal.</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Disburse',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch('<?= $GLOBALS['base_url'] ?>/disburse-batch-payslips', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        payslip_ids: selectedIds,
                        bank_account_id: bankId
                    })
                })
                .then(response => {
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return response.json().then(data => {
                            if (!response.ok || !data.success) {
                                throw new Error(data.message || 'Failed to disburse payroll.');
                            }
                            return data;
                        });
                    } else {
                        return response.text().then(text => {
                            console.error("Server non-JSON response:", text);
                            throw new Error('Server returned an invalid format. Check console.');
                        });
                    }
                })
                .catch(error => {
                    Swal.showValidationMessage(`${error}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Payroll Disbursed Successfully',
                    text: result.value.message || `${selectedIds.length} payslips were disbursed and the journal was posted successfully.`,
                    icon: 'success'
                }).then(() => {
                    window.location.reload();
                });
            }
        });
    }
</script>

<style>
    .status-pending {
    background: #b4f4b5;
    color: #085f06;
}

.status-badge {
    padding: 5px 12px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 600;
}

.card-hover-effect {
    display: block;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.card-hover-effect:hover {
    transform: translateY(-4px);
}
.card-hover-effect:hover .card__wrapper {
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
    border-color: rgba(0, 0, 0, 0.12);
}
</style>



<!-- JS to handle modal and bank details -->