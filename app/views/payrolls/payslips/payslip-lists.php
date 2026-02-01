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
        <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-12">
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
        <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-12">
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

        <!-- Buttons -->
        <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-12 col-12">
            <div class="card__wrapper ">
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <button type="submit" class="btn btn-secondary w-100 px-0">
                        Payslip Search
                    </button>
                    <a href="<?= $GLOBALS['base_url'] ?>/payslip-lists" class="btn btn-success w-100 px-0">
                        Reset
                    </a>

                    <a href="<?= $GLOBALS['base_url'] ?>/create-payslip" class="btn btn-primary w-100 px-0">
                        Create Payslip
                    </a>
                </div>
            </div>
        </div>


    </div>


</form>



<div class="row">
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-12">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-gear"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Employee</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title"><?= $totalEmployees ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-12">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-badge-check"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Paid</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title"><?= $paidCount ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-12">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-user"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Unpaid</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title"><?= $pendingCount ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-none col-xxl-3 col-xl-6 col-lg-4 col-md-4 col-12">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-house-person-leave"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Leave</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title">55</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table hover mb-20" id="">
                    <thead>
                        <tr class="table__title">
                            <th class="th_1">Employee</th>
                            <th>Designation</th>
                            <th>Email</th>
                            <th>Joining Date</th>
                            <th>Salary (Monthly)</th>
                            <th>Approval Time</th>
                            <th>Status</th>
                            <th class="">Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php foreach ($payslips as $payslip): ?>
                            <tr>
                                <td class="td_1">
                                    <span class="table-avatar">
                                        <a class="employee__avatar mr-5" href="<?= $GLOBALS['base_url'] ?>/employee-profile/<?= $payslip['employee_id'] ?>"><img
                                                class="img-48 border-circle"
                                                src="<?= getImage('employees', $payslip['photo']) ?>"
                                                alt="User Image"><strong class="ps-2">EMP-<?= $payslip['employee_id'] ?></strong></a>
                                        <a href="<?= $GLOBALS['base_url'] ?>/employee-profile/<?= $payslip['employee_id'] ?>"><?= $payslip['employee_name'] ?></a>
                                    </span>
                                </td>
                                <td class="table__employee-position"><?= $payslip['desgination_name'] ?></td>
                                <td class="table__employee-email">
                                    <a href="mailto:<?= htmlspecialchars($payslip['email']) ?>">
                                        <?= htmlspecialchars($payslip['email']) ?>
                                    </a>
                                </td>


                                <td class="table__joining-date"><?= $payslip['joining_date'] ?></td>

                                <td class="table__salary"><?= $payslip['net_salary'] ?></td>
                                <td class="table__approval-time">
                                    <?= !empty($payslip['approval_time']) ? date('d M Y H:i', strtotime($payslip['approval_time'])) : 'Not Approved' ?>
                                </td>
                                <td class="table__delivery ">
                                    <?php if ($payslip['status'] == 'paid'): ?>
                                        <span class="bd-badge bg-success">Paid</span>
                                    <?php elseif ($payslip['status'] == 'approved'): ?>
                                        <span class="bd-badge bg-primary">Approved</span>
                                    <?php elseif ($payslip['status'] == 'reject'): ?>
                                        <span class="bd-badge bg-danger">Reject</span>
                                    <?php else: ?>
                                        
                                        <span class="status-badge status-pending">Pending</span>
                                    <?php endif; ?>
                                </td>



                                <td class="able__icon-box">
                                    <div class="d-flex align-items-center justify-content-start gap-10">
                                        <?php if ($payslip['status'] != 'paid'): ?>
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


                                        <a href="<?= $GLOBALS['base_url'] ?>/show-payslip/<?= $payslip['id'] ?>" class="table__icon download">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>
                                        <a href="<?= $GLOBALS['base_url'] ?>/show-payslip/<?= $payslip['id'] ?>?print=1" class="table__icon download">
                                            <i class="fas fa-print"></i>
                                        </a>

                                        <a href="#" class="d-none table__icon edit">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </a>
                                        <button class="d-none removeBtn table__icon delete"><i
                                                class="fa-regular fa-trash"></i></button>
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

    </style>



<!-- JS to handle modal and bank details -->