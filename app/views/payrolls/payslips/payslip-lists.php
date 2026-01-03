<?php
$page_title = "Payroll Lists";
$page = 'Payroll List';
$show_breadcrumb = true;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

    <h4 class="mb-2 mb-md-0">
        Payroll Manage
    </h4>

    <div class="d-flex gap-2">
        <a href="<?= $GLOBALS['base_url'] ?>/create-payslip" class="btn btn-primary"> <i class="fa-light fa-plus me-1"></i> Create Payslip</a>
    </div>

</div>

<div class="row">
    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
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
    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
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
    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
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

    <div class="d-none col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
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
                <table class="table hover mb-20" id="dataTableDefualt">
                    <thead>
                        <tr class="table__title">
                            <th>Employee ID</th>
                            <th>Employee Name</th>
                            <th>Designation</th>
                            <th>Email</th>
                            <th>Joining Date</th>
                            <th>Salary (Monthly)</th>
                            <th>Status</th>
                            <th class="d-none">Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php foreach ($payslips as $payslip): ?>
                            <tr>
                                <td class="table__employee-id">EMP-<?= $payslip['employee_id'] ?></td>
                                <td>
                                    <span class="table-avatar">
                                        <a class="employee__avatar mr-5" href="<?= $GLOBALS['base_url'] ?>/employee-profile/<?= $payslip['employee_id'] ?>"><img
                                                class="img-48 border-circle"
                                                src="assets/images/avatar/avatar1.png"
                                                alt="User Image"></a>
                                        <a href="<?= $GLOBALS['base_url'] ?>/employee-profile/<?= $payslip['employee_id'] ?>"><?= $payslip['employee_name'] ?></a>
                                    </span>
                                </td>
                                <td class="table__employee-position"><?= $payslip['desgination_name'] ?></td>
                                <td class="table__employee-emaill"><a href="<?= $payslip['email'] ?>"><?= $payslip['email'] ?></a></td>
                                <td class="table__joining-date"><?= $payslip['joining_date'] ?></td>
                                <td class="table__salary"><?= $payslip['net_salary'] ?></td>
                                <td class="table__delivery">
                                    <?php if ($payslip['status'] == 'paid'): ?>
                                        <span class="bd-badge bg-success">Approve</span>
                                    <?php else: ?>
                                        <span class="bd-badge bg-danger">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none able__icon-box">
                                    <div class="d-flex align-items-center justify-content-start gap-10">
                                        <button
                                            class=" btn btn-success btn-sm payPayslipBtn"
                                            data-id="<?= $payslip['id'] ?>"
                                            data-earning="<?= $payslip['total_earning'] ?>"
                                            data-deduction="<?= $payslip['total_deduction'] ?>"
                                            data-net="<?= $payslip['net_salary'] ?>">
                                            Pay
                                        </button>

                                        <a href="payroll-payslip.html" class="table__icon download">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>
                                        <a href="#" class="table__icon edit">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </a>
                                        <button class="removeBtn table__icon delete"><i
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
                                                <select name="company_bank_id" class="form-select">
                                                    <option value="">Select Bank</option>
                                                    <?php foreach ($companyBanks as $bank): ?>
                                                        <option value="<?= $bank['id'] ?>">
                                                            <?= $bank['bank_name'] ?> (<?= $bank['account_number'] ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
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
        document.querySelectorAll('.payPayslipBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('modalPayslipId').value = this.dataset.id;
                document.getElementById('modalTotalEarning').value = this.dataset.earning;
                document.getElementById('modalTotalDeduction').value = this.dataset.deduction;
                document.getElementById('modalNetSalary').value = this.dataset.net;

                new bootstrap.Modal(
                    document.getElementById('payPayslipModal')
                ).show();
            })
        })

    })
</script>