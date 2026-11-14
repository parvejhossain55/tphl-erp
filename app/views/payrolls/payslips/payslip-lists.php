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



<?php
$currentYear  = date('Y');
$currentMonth = date('m');
$selectedYear  = $selectedYear ?? $currentYear;
$selectedMonth = $selectedMonth ?? $currentMonth;
$startYear = $currentYear - 10;
$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
?>

<!-- Single-Line Minimal Filter & Action Bar -->
<form action="" method="get" class="mb-3">
    <div class="bg-white rounded-3 px-3 py-2" style="border: 1px solid #e9ecef; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
        <div class="row g-2 align-items-center">
            <!-- Year Select -->
            <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                <select name="year" id="filterYear" class="form-select form-select-sm text-dark fw-medium" style="height: 34px; font-size: 13px; border-color: #dee2e6;">
                    <option value="">Year Select</option>
                    <?php for ($year = $currentYear; $year >= $startYear; $year--): ?>
                        <option value="<?= $year ?>" <?= ($selectedYear == $year) ? 'selected' : '' ?>><?= $year ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Month Select -->
            <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                <select name="month" id="filterMonth" class="form-select form-select-sm text-dark fw-medium" style="height: 34px; font-size: 13px; border-color: #dee2e6;">
                    <option value="">Month Select</option>
                    <?php foreach ($months as $id => $name): ?>
                        <option value="<?= $id ?>" <?= ($selectedMonth == $id) ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status Select -->
            <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                <select name="status" class="form-select form-select-sm text-dark fw-medium" style="height: 34px; font-size: 13px; border-color: #dee2e6;">
                    <option value="">All Status</option>
                    <option value="paid" <?= (isset($selectedStatus) && $selectedStatus == 'paid') ? 'selected' : '' ?>>Paid</option>
                    <option value="unpaid" <?= (isset($selectedStatus) && $selectedStatus == 'unpaid') ? 'selected' : '' ?>>Unpaid</option>
                    <option value="draft" <?= (isset($selectedStatus) && $selectedStatus == 'draft') ? 'selected' : '' ?>>Draft / Certificate</option>
                </select>
            </div>

            <!-- Payroll Project Select -->
            <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                <select name="project_id" id="filterProject" class="form-select form-select-sm text-dark fw-medium" style="height: 34px; font-size: 13px; border-color: #dee2e6;">
                    <option value="">All Projects</option>
                    <?php if (!empty($payroll_projects)): ?>
                        <?php foreach ($payroll_projects as $proj):
                            $projLabelDate = (!empty($proj['is_custom_date']) && !empty($proj['end_date']) && $proj['end_date'] !== '0000-00-00')
                                ? $proj['end_date']
                                : ($proj['date'] ?? '');
                        ?>
                            <option value="<?= htmlspecialchars($proj['id']) ?>"
                                data-date="<?= htmlspecialchars($proj['date'] ?? '') ?>"
                                data-iscustom="<?= htmlspecialchars($proj['is_custom_date'] ?? '0') ?>"
                                data-start="<?= htmlspecialchars($proj['start_date'] ?? '') ?>"
                                data-end="<?= htmlspecialchars($proj['end_date'] ?? '') ?>"
                                <?= (isset($selectedProjectId) && $selectedProjectId == $proj['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($proj['name']) ?>
                                <?= !empty($projLabelDate) ? '(' . date('M Y', strtotime($projLabelDate)) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Action Buttons (Same Line with Proper Spacing) -->
            <div class="col-xl-4 col-lg-4 col-md-12">
                <div class="d-flex align-items-center justify-content-xl-end flex-wrap" style="gap: 8px;">
                    <button type="submit" class="btn btn-success btn-sm px-3 fw-semibold d-inline-flex align-items-center justify-content-center" style="height: 34px; font-size: 12.5px; gap: 5px;">
                        <i class="fa-solid fa-magnifying-glass"></i><span>Search</span>
                    </button>
                    <a href="<?= $GLOBALS['base_url'] ?>/payslip-lists" class="btn btn-light btn-sm px-3 text-secondary fw-semibold d-inline-flex align-items-center justify-content-center" style="height: 34px; font-size: 12.5px; border: 1px solid #dee2e6; gap: 5px;" title="Reset Filters">
                        <i class="fa-solid fa-rotate-left"></i><span>Reset</span>
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/import-payslips" class="btn btn-outline-info btn-sm px-3 fw-semibold d-inline-flex align-items-center justify-content-center" style="height: 34px; font-size: 12.5px; gap: 5px;">
                        <i class="fa-solid fa-file-import"></i><span>Import</span>
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/create-payslip?year=<?= $selectedYear ?>&month=<?= $selectedMonth ?>" class="btn btn-primary btn-sm px-3 fw-semibold d-inline-flex align-items-center justify-content-center" style="height: 34px; font-size: 12.5px; gap: 5px;">
                        <i class="fa-solid fa-plus"></i><span>Create</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Compact Single-Row Summary Cards (6 Cards with Soft Minimal Borders) -->
<div class="row g-2 mb-3">
    <!-- Card 1: Total ERP Employees -->
    <div class="col-xxl-2 col-xl-2 col-lg-4 col-md-4 col-sm-6">
        <a href="<?= $GLOBALS['base_url'] ?>/payslip-lists?year=<?= $selectedYear ?>&month=<?= $selectedMonth ?>&status=<?= !empty($selectedProjectId) ? '&project_id=' . $selectedProjectId : '' ?>" class="text-decoration-none d-block h-100">
            <div class="bg-white rounded-3 h-100 d-flex align-items-center" style="padding: 10px 12px; gap: 10px; border: 1px solid #eaeef2; border-left: 3px solid #64748b; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-2" style="width: 34px; height: 34px; background: #f1f5f9; color: #475569;">
                    <i class="fa-solid fa-server" style="font-size: 13px;"></i>
                </div>
                <div class="text-truncate">
                    <div class="text-muted fw-semibold text-truncate" style="font-size: 11px; line-height: 1.2;">ERP Employees</div>
                    <div class="fw-bold text-dark" style="font-size: 15px; line-height: 1.35;"><?= $totalEmployees ?> <small class="text-muted fw-normal" style="font-size: 11px;">Staff</small></div>
                    <div class="text-muted text-truncate" style="font-size: 10.5px;">ERP Database</div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 2: Total Tipsoi Employees -->
    <div class="col-xxl-2 col-xl-2 col-lg-4 col-md-4 col-sm-6">
        <div class="bg-white rounded-3 h-100 d-flex align-items-center" style="padding: 10px 12px; gap: 10px; border: 1px solid #eaeef2; border-left: 3px solid #0ea5e9; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
            <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-2" style="width: 34px; height: 34px; background: #e0f2fe; color: #0284c7;">
                <i class="fa-solid fa-users-viewfinder" style="font-size: 13px;"></i>
            </div>
            <div class="text-truncate">
                <div class="text-muted fw-semibold text-truncate" style="font-size: 11px; line-height: 1.2;">Tipsoi Employees</div>
                <div class="fw-bold text-dark" style="font-size: 15px; line-height: 1.35;"><span id="tipsoiEmployeesCount">0</span> <small class="text-muted fw-normal" style="font-size: 11px;">Staff</small></div>
                <div class="text-muted text-truncate" style="font-size: 10.5px;">Active in Tipsoi</div>
            </div>
        </div>
    </div>

    <!-- Card 3: Not in ERP (Tipsoi) -->
    <div class="col-xxl-2 col-xl-2 col-lg-4 col-md-4 col-sm-6">
        <a href="javascript:void(0);" id="btnViewUnregistered" class="text-decoration-none d-block h-100">
            <div class="bg-white rounded-3 h-100 d-flex align-items-center" style="padding: 10px 12px; gap: 10px; border: 1px solid #eaeef2; border-left: 3px solid #ef4444; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-2" style="width: 34px; height: 34px; background: #fee2e2; color: #dc2626;">
                    <i class="fa-solid fa-user-slash" style="font-size: 13px;"></i>
                </div>
                <div class="text-truncate">
                    <div class="text-muted fw-semibold text-truncate" style="font-size: 11px; line-height: 1.2;">Not in ERP (Tipsoi)</div>
                    <div class="fw-bold text-dark" style="font-size: 15px; line-height: 1.35;"><span id="unregisteredCount">0</span> <small class="text-muted fw-normal" style="font-size: 11px;">Staff</small></div>
                    <div class="text-danger fw-semibold text-truncate" style="font-size: 10.5px;"><i class="fa-regular fa-eye me-1"></i>View list</div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 4: Total Paid -->
    <div class="col-xxl-2 col-xl-2 col-lg-4 col-md-4 col-sm-6">
        <a href="<?= $GLOBALS['base_url'] ?>/payslip-lists?year=<?= $selectedYear ?>&month=<?= $selectedMonth ?>&status=paid<?= !empty($selectedProjectId) ? '&project_id=' . $selectedProjectId : '' ?>" class="text-decoration-none d-block h-100">
            <div class="bg-white rounded-3 h-100 d-flex align-items-center" style="padding: 10px 12px; gap: 10px; border: 1px solid #eaeef2; border-left: 3px solid #10b981; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-2" style="width: 34px; height: 34px; background: #dcfce7; color: #16a34a;">
                    <i class="fa-solid fa-circle-check" style="font-size: 13px;"></i>
                </div>
                <div class="text-truncate">
                    <div class="text-muted fw-semibold text-truncate" style="font-size: 11px; line-height: 1.2;">Total Paid</div>
                    <div class="fw-bold text-dark" style="font-size: 15px; line-height: 1.35;"><?= $paidCount ?> <small class="text-muted fw-normal" style="font-size: 11px;">Slips</small></div>
                    <div class="text-success fw-semibold text-truncate" style="font-size: 10.5px;">৳<?= number_format($paidSalarySum, 2) ?></div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 5: Total Unpaid -->
    <div class="col-xxl-2 col-xl-2 col-lg-4 col-md-4 col-sm-6">
        <a href="<?= $GLOBALS['base_url'] ?>/payslip-lists?year=<?= $selectedYear ?>&month=<?= $selectedMonth ?>&status=unpaid<?= !empty($selectedProjectId) ? '&project_id=' . $selectedProjectId : '' ?>" class="text-decoration-none d-block h-100">
            <div class="bg-white rounded-3 h-100 d-flex align-items-center" style="padding: 10px 12px; gap: 10px; border: 1px solid #eaeef2; border-left: 3px solid #f59e0b; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-2" style="width: 34px; height: 34px; background: #fef3c7; color: #d97706;">
                    <i class="fa-solid fa-clock" style="font-size: 13px;"></i>
                </div>
                <div class="text-truncate">
                    <div class="text-muted fw-semibold text-truncate" style="font-size: 11px; line-height: 1.2;">Total Unpaid</div>
                    <div class="fw-bold text-dark" style="font-size: 15px; line-height: 1.35;"><?= $pendingCount ?> <small class="text-muted fw-normal" style="font-size: 11px;">Pending</small></div>
                    <div class="fw-semibold text-truncate" style="font-size: 10.5px; color: #d97706;">৳<?= number_format($unpaidSalarySum, 2) ?></div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 6: Total Paid Amount -->
    <div class="col-xxl-2 col-xl-2 col-lg-4 col-md-4 col-sm-6">
        <a href="<?= $GLOBALS['base_url'] ?>/payslip-lists?year=<?= $selectedYear ?>&month=<?= $selectedMonth ?>&status=paid<?= !empty($selectedProjectId) ? '&project_id=' . $selectedProjectId : '' ?>" class="text-decoration-none d-block h-100">
            <div class="bg-white rounded-3 h-100 d-flex align-items-center" style="padding: 10px 12px; gap: 10px; border: 1px solid #eaeef2; border-left: 3px solid #3b82f6; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-2" style="width: 34px; height: 34px; background: #dbeafe; color: #2563eb;">
                    <i class="fa-solid fa-money-bill-wave" style="font-size: 13px;"></i>
                </div>
                <div class="text-truncate">
                    <div class="text-muted fw-semibold text-truncate" style="font-size: 11px; line-height: 1.2;">Total Paid Amount</div>
                    <div class="fw-bold text-primary text-truncate" style="font-size: 14.5px; line-height: 1.35;">৳<?= number_format($paidSalarySum, 2) ?></div>
                    <div class="text-muted text-truncate" style="font-size: 10.5px;">Disbursed Funds</div>
                </div>
            </div>
        </a>
    </div>
</div>

    <div class="col-xxl-12">
        <div class="card__wrapper p-3">
            <!-- Compact Header + Disbursement Action Bar -->
            <?php
            $currRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
            $secRoleId  = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
            $roleNameLc = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
            $canDisbursePayroll = in_array($currRoleId, [1, 3, 6, 30, 33, 34, 35, 36, 38], true)
                || in_array($secRoleId, [1, 3, 6, 30, 33, 34, 35, 36, 38], true)
                || strpos($roleNameLc, 'account') !== false
                || strpos($roleNameLc, 'management') !== false
                || strpos($roleNameLc, 'admin') !== false
                || (function_exists('hasPermission') && hasPermission('accounts manage'));
            ?>
            <div class="d-flex flex-wrap align-items-center justify-content-between pb-3 mb-3 border-bottom" style="gap: 12px;">
                <div class="d-flex align-items-center flex-wrap" style="gap: 16px;">
                    <h6 class="fw-bold m-0 text-dark d-inline-flex align-items-center" style="font-size: 14.5px; gap: 7px;">
                        <i class="fa-solid fa-file-invoice-dollar text-success"></i>
                        <span>Salary Slips</span>
                    </h6>
                    <div class="form-check form-switch m-0 d-inline-flex align-items-center" style="gap: 8px; padding-left: 2.2em;">
                        <input class="form-check-input m-0" type="checkbox" id="filterLwpOnly" style="cursor: pointer; margin-left: -2.2em !important;">
                        <label class="form-check-label text-secondary fw-medium" style="font-size: 12.5px; cursor: pointer;" for="filterLwpOnly">
                            Show LWP / Absent Only
                        </label>
                    </div>
                </div>

                <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                    <?php if ($canDisbursePayroll): ?>
                        <span class="text-muted fw-semibold" style="font-size: 12.5px;">Pay From:</span>
                        <select id="disburse_bank_account_id" class="form-select form-select-sm" style="width: auto; min-width: 195px; height: 32px; font-size: 12.5px; border-color: #dee2e6;">
                            <option value="0">Petty Cash / Cash Account</option>
                            <?php foreach ($companyBanks as $bank): ?>
                                <option value="<?= $bank['id'] ?>">
                                    <?= htmlspecialchars($bank['bank_name']) ?> - <?= htmlspecialchars($bank['account_number']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-success btn-sm px-3 fw-semibold d-inline-flex align-items-center" style="height: 32px; font-size: 12.5px; gap: 6px;" onclick="disburseSelectedPayroll()">
                            <i class="fa-solid fa-money-check-dollar"></i><span>Disburse & Post Journal</span>
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-semibold d-inline-flex align-items-center" style="height: 32px; font-size: 12.5px; gap: 6px;" onclick="sendBulkPayslipEmail()">
                        <i class="fa-regular fa-envelope"></i><span>Email Selected</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm px-3 fw-semibold d-inline-flex align-items-center" style="height: 32px; font-size: 12.5px; gap: 6px;" onclick="bulkDeleteSelected()">
                        <i class="fa-regular fa-trash-can"></i><span>Bulk Delete</span>
                    </button>
                </div>
            </div>
            <div id="lwpDebugBanner" class="d-none"></div>

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
                            <th>Net Payable</th>
                            <th>LWP / Absent</th>
                            <th>Approval Time</th>
                            <th>Status</th>
                            <th class="">Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php $sl = 1; ?>
                        <?php foreach ($payslips as $payslip): ?>
                            <?php if ($payslip['status'] == 'draft' && (empty($selectedStatus) || $selectedStatus !== 'draft')) continue; ?>
                            <tr>
                                <td><input type="checkbox" class="payslip-checkbox" value="<?= $payslip['id'] ?>" <?= empty($payslip['id']) ? 'disabled' : '' ?> data-status="<?= htmlspecialchars($payslip['status']) ?>"></td>
                                <td><?= $sl++ ?></td>
                                <td class="td_1">
                                    <span class="table-avatar">
                                        <a class="employee__avatar mr-5" href="<?= $GLOBALS['base_url'] ?>/employee-profile/<?= $payslip['employee_id'] ?>"><img
                                                class="img-48 border-circle"
                                                src="<?= getImage('employees', $payslip['photo']) ?>"
                                                 alt="User Image"><strong class="ps-2"><?= htmlspecialchars($payslip['employee_office_id'] ?? '') ?></strong></a>
                                         <a href="<?= $GLOBALS['base_url'] ?>/employee-profile/<?= $payslip['employee_id'] ?>"><?= $payslip['employee_name'] ?></a>
                                    </span>
                                    <?php if (empty($selectedProjectId) && !empty($payslip['project_name'])): ?>
                                        <br><span class="badge bg-light text-secondary border px-1 py-0 mt-1" style="font-size: 10px;" title="Payroll Project">
                                            <i class="fa-regular fa-folder-open me-1"></i><?= htmlspecialchars($payslip['project_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($payslip['email_sent_at'])): ?>
                                        <br><span class="badge bg-success-subtle text-success border border-success-subtle px-1 py-0 mt-1" style="font-size: 10px;" title="Emailed on <?= date('d M Y H:i', strtotime($payslip['email_sent_at'])) ?>">
                                            <i class="fa-solid fa-circle-check me-1"></i>Emailed
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="table__employee-position"><?= $payslip['desgination_name'] ?></td>
                                <td class="table__employee-department"><?= htmlspecialchars($payslip['department_name'] ?? 'N/A') ?></td>
                                <td class="table__employee-email">
                                    <a href="mailto:<?= htmlspecialchars((string)($payslip['email'] ?? '')) ?>">
                                        <?= htmlspecialchars((string)($payslip['email'] ?? '')) ?>
                                    </a>
                                </td>


                                <td class="table__joining-date"><?= $payslip['joining_date'] ?></td>

                                <td class="table__salary"><?= number_format((float)($payslip['emp_gross_salary'] ?? 0), 2) ?></td>
                                <td class="table__net-salary fw-bold text-success"><?= number_format((float)($payslip['net_salary'] ?? 0), 2) ?></td>
                                <?php
                                $lwpAmount = (float)($payslip['lwp_amount'] ?? ($payslip['unpaid_absent_amount'] ?? 0));
                                $grossSalary = (float)($payslip['emp_gross_salary'] ?? 0);
                                $savedLwpDays = isset($payslip['late_unpaid_absent_days']) ? (float)$payslip['late_unpaid_absent_days'] : 0.0;
                                $rowCycleDays = !empty($payslip['cycle_days']) ? (int)$payslip['cycle_days'] : 30;
                                $phpLwpDays = 0;
                                if ($savedLwpDays > 0) {
                                    $phpLwpDays = $savedLwpDays;
                                } elseif ($lwpAmount > 0 && $grossSalary > 0 && $rowCycleDays > 0) {
                                    $perDay = $grossSalary / $rowCycleDays;
                                    $phpLwpDays = round($lwpAmount / $perDay, 2);
                                }
                                ?>
                                <td class="table__lwp-days text-center" 
                                    data-emp-office-id="<?= htmlspecialchars((string)($payslip['employee_office_id'] ?? '')) ?>" 
                                    data-emp-id="<?= $payslip['employee_id'] ?>" 
                                    data-emp-name="<?= htmlspecialchars((string)($payslip['employee_name'] ?? '')) ?>"
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
                                             <a href="<?= $GLOBALS['base_url'] ?>/create-payslip?year=<?= $selectedYear ?>&month=<?= $selectedMonth ?>&employee_id=<?= $payslip['employee_id'] ?>" class="btn btn-info btn-sm">
                                                 <i class="fa-light fa-plus me-1"></i> Create Payslip
                                             </a>
                                         <?php else: ?>
                                             <?php if ($payslip['status'] == 'draft'): ?>
                                                 <span class="badge bg-secondary-subtle text-secondary border py-2 px-2" style="font-size: 11px;">Draft Certificate</span>
                                             <?php elseif ($payslip['status'] != 'paid'): ?>
                                                 <?php if ($canDisbursePayroll): ?>
                                                     <button
                                                         class="btn btn-warning btn-sm payPayslipBtn"
                                                         data-id="<?= $payslip['id'] ?>"
                                                         data-earning="<?= $payslip['total_earning'] ?>"
                                                         data-deduction="<?= $payslip['total_deduction'] ?>"
                                                         data-net="<?= $payslip['net_salary'] ?>">
                                                         Payment
                                                     </button>
                                                 <?php else: ?>
                                                     <span class="badge bg-warning-subtle text-dark border border-warning-subtle py-1 px-2" style="font-size: 11px;" title="Accounts department will disburse payment">
                                                         Accounts Payout
                                                     </span>
                                                 <?php endif; ?>
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
                                             <button type="button" class="table__icon text-primary border-0 bg-transparent p-0" title="Email Payslip" onclick="sendSinglePayslipEmail(<?= $payslip['id'] ?>, '<?= htmlspecialchars(addslashes((string)($payslip['employee_name'] ?? ''))) ?>', '<?= htmlspecialchars(addslashes((string)($payslip['email'] ?? ''))) ?>')">
                                                 <i class="fa-regular fa-envelope"></i>
                                             </button>
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

<?php if (empty($selectedProjectId)): ?>
<div class="col-xxl-12 animate__animated animate__fadeIn mt-4">
    <div class="card__wrapper">
        <div class="d-flex justify-content-between align-items-center mb-15 flex-wrap gap-2">
            <h5 class="fw-bold m-0 text-dark" style="font-size: 1rem;">
                Draft Salary Certificates <span class="badge bg-secondary ms-2"><?= count($draftPayslips ?? []) ?></span>
            </h5>
            <div>
                <button type="button" class="btn btn-primary btn-sm px-3 fw-bold" onclick="sendBulkDraftPayslipEmail()">
                    <i class="fa-light fa-envelope me-1"></i> Email Selected Certificates
                </button>
            </div>
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
                        <th>Net Payable</th>
                        <th>Status</th>
                        <th class="">Action</th>
                    </tr>
                </thead>
                <tbody class="table__body">
                    <?php if (!empty($draftPayslips)): ?>
                        <?php $sl2 = 1; ?>
                        <?php foreach ($draftPayslips as $payslip): ?>
                            <tr>
                                <td><input type="checkbox" class="draft-payslip-checkbox payslip-checkbox" value="<?= $payslip['id'] ?>"></td>
                                <td><?= $sl2++ ?></td>
                                <td class="td_1">
                                    <span class="table-avatar">
                                        <a class="employee__avatar mr-5" href="<?= $GLOBALS['base_url'] ?>/employee-profile/<?= $payslip['employee_id'] ?>"><img class="img-48 border-circle" src="<?= getImage('employees', $payslip['photo']) ?>" alt="User Image"><strong class="ps-2"><?= htmlspecialchars($payslip['employee_office_id'] ?? '') ?></strong></a>
                                        <a href="<?= $GLOBALS['base_url'] ?>/employee-profile/<?= $payslip['employee_id'] ?>"><?= $payslip['employee_name'] ?></a>
                                    </span>
                                    <?php if (!empty($payslip['email_sent_at'])): ?>
                                        <br><span class="badge bg-success-subtle text-success border border-success-subtle px-1 py-0 mt-1" style="font-size: 10px;" title="Emailed on <?= date('d M Y H:i', strtotime($payslip['email_sent_at'])) ?>">
                                            <i class="fa-solid fa-circle-check me-1"></i>Emailed
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="table__employee-position"><?= $payslip['desgination_name'] ?></td>
                                <td class="table__employee-department"><?= htmlspecialchars($payslip['department_name'] ?? 'N/A') ?></td>
                                <td class="table__employee-email"><a href="mailto:<?= htmlspecialchars((string)($payslip['email'] ?? '')) ?>"><?= htmlspecialchars((string)($payslip['email'] ?? '')) ?></a></td>
                                <td class="table__joining-date"><?= $payslip['joining_date'] ?></td>
                                <td class="table__salary"><?= number_format((float)($payslip['emp_gross_salary'] ?? 0), 2) ?></td>
                                <td class="table__net-salary fw-bold text-success"><?= number_format((float)($payslip['net_salary'] ?? 0), 2) ?></td>
                                <td class="table__delivery"><span class="bd-badge bg-secondary">Draft / Certificate</span></td>
                                <td class="able__icon-box">
                                    <div class="d-flex align-items-center justify-content-start gap-10">
                                        <span class="badge bg-secondary-subtle text-secondary border py-2 px-2" style="font-size: 11px;">Draft Certificate</span>
                                        <a href="<?= $GLOBALS['base_url'] ?>/show-payslip/<?= $payslip['id'] ?>" class="table__icon download" title="View Payslip"><i class="fa-regular fa-eye"></i></a>
                                        <a href="<?= $GLOBALS['base_url'] ?>/show-payslip/<?= $payslip['id'] ?>?print=1" class="table__icon download" title="Print Payslip"><i class="fas fa-print"></i></a>
                                        <button type="button" class="table__icon text-primary border-0 bg-transparent p-0" title="Email Salary Certificate" onclick="sendSinglePayslipEmail(<?= $payslip['id'] ?>, '<?= htmlspecialchars(addslashes((string)($payslip['employee_name'] ?? ''))) ?>', '<?= htmlspecialchars(addslashes((string)($payslip['email'] ?? ''))) ?>')">
                                            <i class="fa-regular fa-envelope"></i>
                                        </button>
                                        <a href="<?= $GLOBALS['base_url'] ?>/delete-payslip/<?= $payslip['id'] ?>" onclick="return confirm('Are you sure you want to delete this payslip?')" class="table__icon text-danger" title="Delete Payslip"><i class="fa-regular fa-trash-can"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center text-muted py-3">No draft salary certificates found for this month.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
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
        // Fetch and Populate LWP Days for Selected Cycle (26th–25th or Custom Project)
        // -------------------------------------------------------------
        <?php
        $lwpCycleYear  = (int) ($selectedYear ?: date('Y'));
        $lwpCycleMonth = (int) ($selectedMonth ?: date('m'));
        $prevMonthNum  = $lwpCycleMonth === 1 ? 12 : ($lwpCycleMonth - 1);
        $prevYearNum   = $lwpCycleMonth === 1 ? ($lwpCycleYear - 1) : $lwpCycleYear;
        $cycleStartForLwp = sprintf('%04d-%02d-26', $prevYearNum, $prevMonthNum);
        $cycleEndForLwp   = sprintf('%04d-%02d-25', $lwpCycleYear, $lwpCycleMonth);
        if (!empty($selectedProjectId) && !empty($payroll_projects)) {
            foreach ($payroll_projects as $pRowLwp) {
                if ((int) ($pRowLwp['id'] ?? 0) === (int) $selectedProjectId) {
                    if (!empty($pRowLwp['is_custom_date']) && !empty($pRowLwp['start_date']) && !empty($pRowLwp['end_date']) && $pRowLwp['start_date'] !== '0000-00-00' && $pRowLwp['end_date'] !== '0000-00-00') {
                        $cycleStartForLwp = substr($pRowLwp['start_date'], 0, 10);
                        $cycleEndForLwp   = substr($pRowLwp['end_date'], 0, 10);
                    } elseif (!empty($pRowLwp['date'])) {
                        $dp = explode('-', $pRowLwp['date']);
                        if (count($dp) >= 2) {
                            $py = (int) $dp[0];
                            $pm = (int) $dp[1];
                            $prevM = $pm === 1 ? 12 : ($pm - 1);
                            $prevY = $pm === 1 ? ($py - 1) : $py;
                            $cycleStartForLwp = sprintf('%04d-%02d-26', $prevY, $prevM);
                            $cycleEndForLwp   = sprintf('%04d-%02d-25', $py, $pm);
                        }
                    }
                    break;
                }
            }
        }
        ?>
        const base_url = '<?= $GLOBALS['base_url'] ?>';
        const selectedYear = "<?= $selectedYear ?>";
        const selectedMonth = "<?= $selectedMonth ?>";

        const firstDay = "<?= $cycleStartForLwp ?>";
        const lastDay = "<?= $cycleEndForLwp ?>";

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
            const sClean = String(startDateStr || '').slice(0, 10);
            const eClean = String(endDateStr || '').slice(0, 10);
            if (!sClean || !eClean) return dates;
            let current = new Date(sClean + 'T00:00:00');
            const end = new Date(eClean + 'T00:00:00');
            while (current <= end) {
                const y = current.getFullYear();
                const m = String(current.getMonth() + 1).padStart(2, '0');
                const day = String(current.getDate()).padStart(2, '0');
                dates.push(`${y}-${m}-${day}`);
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
                        
                        dates.forEach(d => {
                            if (d >= firstDay && d <= lastDay) {
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

                // Keep debug banner hidden in production UI

                const formatUnpaidDays = (val) => {
                    const num = parseFloat(val) || 0;
                    return Number.isInteger(num) ? String(num) : String(parseFloat(num.toFixed(2)));
                };

                cells.forEach(cell => {
                    const rawId = cell.dataset.empOfficeId || '';
                    const officeId = normalizeId(rawId);
                    const empName = cell.dataset.empName || '';
                    const parentRow = cell.closest('tr');
                    const phpLwpDays = parseFloat(cell.getAttribute('data-php-lwp-days') || '0') || 0;
                    const datesArray = empGroups[officeId] ? Array.from(empGroups[officeId].datesList).sort() : [];
                    const lwpLeaveCount = datesArray.length;
                    const totalUnpaidDays = phpLwpDays > 0 ? phpLwpDays : lwpLeaveCount;

                    parentRow.setAttribute('data-lwp-dates-json', JSON.stringify(datesArray));

                    if (totalUnpaidDays > 0) {
                        const dispDays = formatUnpaidDays(totalUnpaidDays);
                        parentRow.setAttribute('data-lwp-days', String(totalUnpaidDays));
                        const eyeBtnHtml = lwpLeaveCount > 0 ? `
                            <button type="button" class="btn btn-xs btn-link p-0 text-decoration-none view-lwp-breakdown" 
                                    title="View Approved LWP Dates"
                                    data-name="${empName}" 
                                    data-total="${dispDays}"
                                    data-count="${lwpLeaveCount}"
                                    data-id="${officeId}">
                                <i class="fa-regular fa-eye text-primary" style="font-size:0.85rem;"></i>
                            </button>
                        ` : '';
                        cell.innerHTML = `
                            <div class="d-flex align-items-center justify-content-center gap-1 animate__animated animate__fadeIn">
                                <span class="badge bg-danger-subtle text-danger px-2 py-1 fw-bold border border-danger-subtle">${dispDays} Days</span>
                                ${eyeBtnHtml}
                            </div>
                        `;
                    } else {
                        parentRow.setAttribute('data-lwp-days', '0');
                        cell.innerHTML = `<span class="text-muted" style="font-size: 0.85rem;">—</span>`;
                    }
                });

                document.querySelectorAll('.view-lwp-breakdown').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const name = this.dataset.name;
                        const totalDays = this.dataset.total || this.dataset.count;
                        const count = this.dataset.count;
                        const row = this.closest('tr');
                        const dates = JSON.parse(row.getAttribute('data-lwp-dates-json') || '[]');

                        const formattedDates = dates.map(d => {
                            const dObj = new Date(d);
                            return `<li class="mb-1 text-muted text-start" style="padding-left:15px; text-indent: -15px;"><i class="fa-regular fa-calendar-days text-danger me-2"></i><strong>${dObj.toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' })}</strong></li>`;
                        }).join('');

                        Swal.fire({
                            title: `<span style="font-size: 1.15rem; font-weight: 700; color: #333;"><i class="fa-solid fa-calendar-xmark text-danger me-2"></i> LWP / Absent Breakdown</span>`,
                            html: `
                                <div class="text-start fs-13 mt-3" style="font-family:'Inter',sans-serif; line-height: 1.6;">
                                    <p class="mb-2 text-dark"><strong>Employee Name:</strong> <span class="text-muted">${name}</span></p>
                                    <p class="mb-2 text-dark"><strong>Month:</strong> <span class="text-muted">${selectedMonthName} ${selectedYear}</span></p>
                                    <p class="mb-2 text-dark"><strong>Total LWP / Absent:</strong> <span class="badge bg-danger fs-12 px-2 py-1">${totalDays} Days</span></p>
                                    <p class="mb-3 text-dark"><strong>Approved LWP Leave:</strong> <span class="badge bg-warning text-dark fs-12 px-2 py-1">${count} Days</span></p>
                                    <hr class="my-2">
                                    <p class="mb-2 fw-bold text-secondary">Approved LWP Dates:</p>
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
                    const phpLwpDays = parseFloat(cell.getAttribute('data-php-lwp-days') || '0') || 0;
                    const parentRow = cell.closest('tr');
                    if (phpLwpDays > 0) {
                        const dispDays = Number.isInteger(phpLwpDays) ? String(phpLwpDays) : String(parseFloat(phpLwpDays.toFixed(2)));
                        parentRow.setAttribute('data-lwp-days', String(phpLwpDays));
                        cell.innerHTML = `
                            <div class="d-flex align-items-center justify-content-center gap-1 animate__animated animate__fadeIn">
                                <span class="badge bg-danger-subtle text-danger px-2 py-1 fw-bold border border-danger-subtle">${dispDays} Days</span>
                            </div>
                        `;
                    } else {
                        parentRow.setAttribute('data-lwp-days', '0');
                        cell.innerHTML = `<span class="text-muted" style="font-size: 0.85rem;">—</span>`;
                    }
                });
            });

        // -------------------------------------------------------------
        // LWP / Absent Only Filter switch toggle
        // -------------------------------------------------------------
        const filterLwpCheckbox = document.getElementById('filterLwpOnly');
        if (filterLwpCheckbox) {
            filterLwpCheckbox.addEventListener('change', function() {
                const checked = this.checked;
                const rows = document.querySelectorAll('#payslipListTable tbody tr');
                
                let foundMatch = false;
                rows.forEach(row => {
                    const lwpDays = parseFloat(row.getAttribute('data-lwp-days') || '0') || 0;
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
                            No employees have LWP / Absent in ${selectedMonthName} ${selectedYear}.
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
        let paidSkipped = 0;
        document.querySelectorAll('.payslip-checkbox:checked').forEach(cb => {
            if (cb.value) {
                if (cb.dataset.status === 'paid') {
                    paidSkipped++;
                } else {
                    selectedIds.push(cb.value);
                }
            }
        });

        if (selectedIds.length === 0) {
            if (paidSkipped > 0) {
                Swal.fire('Info', 'Paid payslips cannot be deleted.', 'info');
            } else {
                Swal.fire('Warning', 'Please select at least one unpaid payslip to delete.', 'warning');
            }
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
        let alreadyPaidCount = 0;
        document.querySelectorAll('#payslipListTable .payslip-checkbox:checked').forEach(cb => {
            if (cb.value) {
                if (cb.dataset.status === 'paid') {
                    alreadyPaidCount++;
                } else {
                    selectedIds.push(cb.value);
                }
            }
        });

        if (selectedIds.length === 0) {
            if (alreadyPaidCount > 0) {
                Swal.fire('Info', 'All selected payslips are already paid/disbursed.', 'info');
            } else {
                Swal.fire('Warning', 'Please select at least one unpaid payslip to disburse.', 'warning');
            }
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

    function sendSinglePayslipEmail(payslipId, empName, empEmail) {
        if (!empEmail || empEmail.trim() === '') {
            Swal.fire({
                icon: 'warning',
                title: 'No Email Configured',
                text: `Employee "${empName}" does not have an email address in their profile. Please update their email first.`,
                confirmButtonColor: '#0f766e'
            });
            return;
        }

        Swal.fire({
            title: 'Send Payslip via Email?',
            html: `Send the official payslip and PDF attachment to:<br><br><b>${empName}</b><br><span class="text-primary font-monospace">&lt;${empEmail}&gt;</span>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fa-regular fa-paper-plane me-1"></i> Yes, Send Email',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#0f766e',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch('<?= $GLOBALS['base_url'] ?>/send-payslip-email', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ payslip_id: payslipId })
                })
                .then(res => {
                    const contentType = res.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return res.json().then(data => {
                            if (!res.ok || !data.success) {
                                throw new Error(data.message || 'Failed to send email.');
                            }
                            return data;
                        });
                    } else {
                        return res.text().then(text => {
                            console.error("Non-JSON:", text);
                            throw new Error('Server returned an invalid format. Check console.');
                        });
                    }
                })
                .catch(err => {
                    Swal.showValidationMessage(`${err.message}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then(result => {
            if (result.isConfirmed && result.value) {
                Swal.fire({
                    icon: 'success',
                    title: 'Email Sent!',
                    text: result.value.message || 'Payslip has been successfully emailed.',
                    confirmButtonColor: '#0f766e'
                }).then(() => {
                    window.location.reload();
                });
            }
        });
    }

    function sendBulkPayslipEmail() {
        const selectedIds = [];
        document.querySelectorAll('#payslipListTable .payslip-checkbox:checked').forEach(cb => {
            if (cb.value) {
                selectedIds.push(cb.value);
            }
        });

        if (selectedIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Payslips Selected',
                text: 'Please select at least one employee payslip using the checkboxes.',
                confirmButtonColor: '#0f766e'
            });
            return;
        }

        Swal.fire({
            title: 'Send Payslips in Bulk?',
            html: `Are you sure you want to send payslips via email to <b>${selectedIds.length}</b> selected employee(s)?<br><br><small class="text-muted">Each employee will receive their personalized email summary with the official PDF payslip attached.</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fa-regular fa-paper-plane me-1"></i> Yes, Send to All',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#0f766e',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch('<?= $GLOBALS['base_url'] ?>/send-bulk-payslip-email', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ payslip_ids: selectedIds })
                })
                .then(res => {
                    const contentType = res.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return res.json().then(data => {
                            if (!res.ok || !data.success) {
                                throw new Error(data.message || 'Bulk email delivery encountered an issue.');
                            }
                            return data;
                        });
                    } else {
                        return res.text().then(text => {
                            console.error("Non-JSON:", text);
                            throw new Error('Server returned an invalid format. Check console.');
                        });
                    }
                })
                .catch(err => {
                    Swal.showValidationMessage(`${err.message}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then(result => {
            if (result.isConfirmed && result.value) {
                const data = result.value;
                let reportHtml = `<p class="fs-14">${data.message}</p>`;
                if (data.errors && data.errors.length > 0) {
                    reportHtml += `<hr><div class="text-start fs-12 text-muted" style="max-height:160px; overflow-y:auto;"><ul class="ps-3 mb-0">`;
                    data.errors.forEach(err => {
                        reportHtml += `<li>${err}</li>`;
                    });
                    reportHtml += `</ul></div>`;
                }

                Swal.fire({
                    icon: data.sent_count > 0 ? 'success' : 'warning',
                    title: 'Email Delivery Complete',
                    html: reportHtml,
                    confirmButtonColor: '#0f766e'
                }).then(() => {
                    window.location.reload();
                });
            }
        });
    }

    function sendBulkDraftPayslipEmail() {
        const selectedIds = [];
        document.querySelectorAll('#draftPayslipListTable .draft-payslip-checkbox:checked').forEach(cb => {
            if (cb.value) {
                selectedIds.push(cb.value);
            }
        });

        if (selectedIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Certificates Selected',
                text: 'Please select at least one draft salary certificate using the checkboxes.',
                confirmButtonColor: '#0f766e'
            });
            return;
        }

        Swal.fire({
            title: 'Send Draft Certificates in Bulk?',
            html: `Are you sure you want to send draft salary certificates via email to <b>${selectedIds.length}</b> selected employee(s)?<br><br><small class="text-muted">Each employee will receive their personalized email summary with the official PDF certificate attached.</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fa-regular fa-paper-plane me-1"></i> Yes, Send to All',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#0f766e',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch('<?= $GLOBALS['base_url'] ?>/send-bulk-payslip-email', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ payslip_ids: selectedIds })
                })
                .then(res => {
                    const contentType = res.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return res.json().then(data => {
                             if (!res.ok || !data.success) {
                                 throw new Error(data.message || 'Bulk email delivery encountered an issue.');
                             }
                             return data;
                        });
                    } else {
                        return res.text().then(text => {
                             console.error("Non-JSON:", text);
                             throw new Error('Server returned an invalid format. Check console.');
                        });
                    }
                })
                .catch(err => {
                    Swal.showValidationMessage(`${err.message}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then(result => {
            if (result.isConfirmed && result.value) {
                const data = result.value;
                let reportHtml = `<p class="fs-14">${data.message}</p>`;
                if (data.errors && data.errors.length > 0) {
                    reportHtml += `<hr><div class="text-start fs-12 text-muted" style="max-height:160px; overflow-y:auto;"><ul class="ps-3 mb-0">`;
                    data.errors.forEach(err => {
                        reportHtml += `<li>${err}</li>`;
                    });
                    reportHtml += `</ul></div>`;
                }

                Swal.fire({
                    icon: data.sent_count > 0 ? 'success' : 'warning',
                    title: 'Email Delivery Complete',
                    html: reportHtml,
                    confirmButtonColor: '#0f766e'
                }).then(() => {
                    window.location.reload();
                });
            }
        });
    }

    // Auto-sync Project Date with Year and Month Selects
    document.addEventListener('DOMContentLoaded', function () {
        const projectSelect = document.getElementById('filterProject');
        const yearSelect = document.getElementById('filterYear');
        const monthSelect = document.getElementById('filterMonth');

        if (projectSelect) {
            projectSelect.addEventListener('change', function () {
                const opt = this.options[this.selectedIndex];
                if (opt && opt.value) {
                    const isCustom = opt.getAttribute('data-iscustom') === '1';
                    let targetDate = '';
                    if (isCustom) {
                        targetDate = opt.getAttribute('data-end') || opt.getAttribute('data-start') || '';
                    } else {
                        targetDate = opt.getAttribute('data-date') || '';
                    }
                    if (targetDate) {
                        const parts = targetDate.split('-');
                        if (parts.length >= 2) {
                            if (yearSelect) yearSelect.value = parts[0];
                            if (monthSelect) monthSelect.value = parseInt(parts[1], 10);
                        }
                    }
                }
            });
        }
    });
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