    <?php
    $page = isset($page) ? $page : 'Payslip';
    $show_breadcrumb = true;

    ?>



    <div class=" row justify-content-center" id="payslipContainer">
        <div class="col-xl-12 col-md-12">
            <div class="card__wrapper">
                <div class="mb-20 text-center">
                    <h5 class="card__heading-title">Employee Payslip</h5>
                </div>
                <div class="d-flex justify-content-between flex-xl-row flex-sm-row flex-column">
                    <div class="payslip__office-address">
                        <div class="payslip__logo mb-20">
                            <img class="light-logo d-none" src="assets/images/logo/logo.svg" alt="payslip logo">
                            <img class="dark-logo" src="<?= getImage('settings', getSetting('logo')); ?>" alt="payslip logo">
                        </div>
                        <p><?= getSetting('website_title') ?></p>
                        <p><?= getSetting('address') ?></p>
                        <p><?= getSetting('email') ?></p>
                        <p><?= getSetting('phone') ?></p>
                    </div>
                    <div class="payslip__serial-number">
                        <div class="mb-10">
                            <h5 class="card__heading-title">PAYSLIP #MZ-<?= $payslip['id'] ?></h5>
                        </div>
                        <div class="mb-5">
                            <span>Create Payslip:</span>
                            <span><?= $payslip['created_at'] ?></span>
                        </div>
                        <div class="mb-5">
                            <span>Date Due:</span>
                            <span><?= $payslip['updated_at'] ?></span>
                        </div>
                    </div>
                </div>
                <div class="payslip-line"></div>
                <div class="row g-60 gy-20">
                    <div class="col-xl-6 col-lg-6 col-sm-6">
                        <div class="mb-20">
                            <h4>Billing Address</h4>
                        </div>
                        <div class="payslip__employee-address">
                            <h5 class="mb-10 fw-600"><?= $payslip['employee_name'] ?></h5>
                            <p class="text-muted">Position: <span><?= $payslip['designation_name'] ?></span> </p>
                            <p class="text-muted">Department: <span> <?= $payslip['department_name'] ?></span></p>
                            <p class="text-muted">Email: <span><?= $payslip['email'] ?></span></p>
                            <p class="text-muted">Phone: <span><?= $payslip['phone'] ?></span></p>
                        </div>
                    </div>
                </div>
                <div class="payslip-line"></div>
                <div class="payslip__table table-responsive">
                    <table class="table table-nowrap align-middle mb-0">

                        <!-- ================= EARNINGS ================= -->
                        <thead>
                            <tr class="table__title bg-title">
                                <th>Earning</th>
                                <th>Details</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>

                            <!-- Basic Salary -->
                            <tr>
                                <td>Basic Salary</td>
                                <td>-</td>
                                <td class="text-end payslip-amount">
                                    <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                                    <?= number_format($payslip['basic_salary'], 2) ?>
                                </td>
                            </tr>

                            <!-- Dynamic Earnings -->
                            <?php $earningTotal = $payslip['basic_salary']; ?>
                            <?php foreach ($payslip['items'] as $item): ?>
                                <?php if ($item['item_type'] === 'earning'): ?>
                                    <?php $earningTotal += $item['amount']; ?>
                                    <tr>
                                        <td><?= $item['item_name'] ?></td>
                                        <td>-</td>
                                        <td class="text-end payslip-amount">
                                            <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                                            <?= number_format($item['amount'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <!-- Earning Total -->
                            <tr class="payslip-total fw-bold">
                                <td colspan="2" class="text-end">Total Earnings</td>
                                <td class="text-end">
                                    <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                                    <?= number_format($earningTotal, 2) ?>
                                </td>
                            </tr>

                        </tbody>

                        <!-- ================= DEDUCTIONS ================= -->
                        <thead>
                            <tr class="table__title bg-title">
                                <th>Deduction</th>
                                <th>Details</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php $deductionTotal = 0; ?>
                            <?php foreach ($payslip['items'] as $item): ?>
                                <?php if ($item['item_type'] === 'deduction'): ?>
                                    <?php $deductionTotal += $item['amount']; ?>
                                    <tr>
                                        <td><?= $item['item_name'] ?></td>
                                        <td>-</td>
                                        <td class="text-end payslip-amount text-danger">
                                            <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                                            <?= number_format($item['amount'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <!-- Deduction Total -->
                            <tr class="payslip-total fw-bold">
                                <td colspan="2" class="text-end">Total Deductions</td>
                                <td class="text-end text-danger">
                                    <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                                    <?= number_format($deductionTotal, 2) ?>
                                </td>
                            </tr>

                        </tbody>

                        <!-- ================= SUMMARY ================= -->
                        <tbody>
                            <tr class="invoice-line">
                                <td colspan="3" class="p-0 text-end">
                                    <table class="table table-borderless mb-0 mt-15">
                                        <tbody>
                                            <tr>
                                                <th class="text-start">Net Salary</th>
                                                <th class="text-end">
                                                    <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                                                    <?= number_format($payslip['net_salary'], 2) ?>
                                                </th>
                                            </tr>
                                            <tr class="d-none">
                                                <td class="text-start d-none">Rebate</td>
                                                <td class="text-end d-none">
                                                    <i class="fa-solid fa-bangladeshi-taka-sign"></i> 0.00
                                                </td>
                                            </tr>
                                            <tr class="fw-bold d-none">
                                                <th class="text-start">Payable Amount</th>
                                                <th class="text-end text-success">
                                                    <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                                                    <?= number_format($payslip['net_salary'], 2) ?>
                                                </th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>

                    </table>
                </div>

                <div class="payslip__payment-details mt-25">
                    <label class="field-label">Amount in Words</label>
                    <p class="fw-semibold mt-1 text-uppercase">
                        <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                        <?= amountInWords($payslip['net_salary']) ?>
                    </p>
                </div>



                <div class="payslip__payment-details mt-25">
                    <?php if ($payslip['status'] === 'paid'): ?>

                        <h5 class="card__heading-title mb-15">Payment Details:</h5>

                        <p class="text-muted">
                            Payment Method:
                            <span class="fw-semibold">
                                <?= ucfirst($payslip['payment_method']) ?>
                            </span>
                        </p>

                        <?php if ($payslip['payment_method'] === 'bank'): ?>
                            <p class="text-muted">
                                Bank Name:
                                <span><?= htmlspecialchars($payslip['bank_name'] ?? '-') ?></span>
                            </p>
                            <p class="text-muted">
                                Account Name:
                                <span><?= htmlspecialchars($payslip['account_name'] ?? '-') ?></span>
                            </p>
                            <p class="text-muted">
                                Account Number:
                                <span><?= htmlspecialchars($payslip['account_number'] ?? '-') ?></span>
                            </p>
                        <?php endif; ?>

                    <?php else: ?>

                        <h5 class="card__heading-title mb-15">Payment Status:</h5>
                        <span class="bd-badge bg-danger">Pending</span>

                    <?php endif; ?>

                </div>
                <div class="payslip-line"></div>
                <div class="row justify-content-center" id="payslipContainer">
                    <!-- Your full payslip HTML here -->
                </div>

                <div class="d-flex flex-wrap gap-10 justify-content-lg-end buttons-wrapper">
                    <button type="button" class="btn btn-info" onclick="printPayslip()">
                        <i class="fa-sharp fa-regular fa-eye"></i> Print
                        </button>
                        <a href="<?= $GLOBALS['base_url'] ?>/download-payslip/<?= $payslip['id'] ?>" target="_blank" class="btn btn-warning">
                            <i class="fa-sharp fa-thin fa-file-arrow-down"></i> Download PDF
                        </a>
                </div>

            </div>
        </div>
    </div>




   