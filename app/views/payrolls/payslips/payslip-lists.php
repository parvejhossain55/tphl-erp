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
                            <th>Action</th>
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
                                <td class="table__icon-box">
                                    <div class="d-flex align-items-center justify-content-start gap-10">
                                        <button
                                            class="d-none btn btn-success btn-sm payPayslipBtn"
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


<div class="modal fade" id="payPayslipModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="<?= $GLOBALS['base_url'] ?>/pay-payslip">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Pay Payslip</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="payslip_id" id="modalPayslipId">

                    <!-- Salary Summary -->
                    <div class="mb-3">
                        <label class="form-label">Total Earning</label>
                        <input type="text" id="modalTotalEarning" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Total Deduction</label>
                        <input type="text" id="modalTotalDeduction" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Net Salary</label>
                        <input type="text" id="modalNetSalary" class="form-control fw-bold" readonly>
                    </div>

                    <!-- Payment Method -->
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" id="paymentMethod" class="form-select" required>
                            <option value="">Select</option>
                            <option value="bank">Bank</option>
                            <option value="cash">Cash</option>
                            <option value="mobile_banking">Mobile Banking</option>
                        </select>
                    </div>

                    <!-- Company Bank -->
                    <div class="mb-3 d-none" id="companyBankWrapper">
                        <label class="form-label">Company Bank</label>
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

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">
                        Confirm & Pay
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>




<script>
    document.addEventListener("DOMContentLoaded",function(){
        document.querySelectorAll('.payPayslipBtn').forEach(btn=>{
            btn.addEventListener('click',function(){
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
