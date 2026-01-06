<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Payslip - <?= $payslip['employee_name'] ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <!-- Place favicon.ico in the root directory -->
    <link rel="shortcut icon" type="image/x-icon" href="<?= $GLOBALS['base_url'] ?>/assets/images/logo/tphl_logo.png">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box
        }

        body {
            font-family: Poppins, sans-serif;
            background: #f4f6f8;
            color: #111;
            padding: 20px;
            font-size: 14px;
        }

        /* Wrapper */
        .payslip-wrapper {
            max-width: 900px;
            margin: auto;
            background: #fff;
            border: 1px solid #d1d5db;
        }

        /* Header */
        .company-header {
            padding: 20px;
            text-align: center;
            border-bottom: 3px solid #0f766e;
        }

        .payslip-main-title {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 2px;
        }

        .company-title {
            font-size: 14px;
            margin-bottom: 10px;
        }

        .payslip-id {
            font-size: 12px;
            color: #555;
        }

        /* Section */
        .payslip-content {
            padding: 24px
        }


        /* Mobile responsive (375px - 440px 이하) */
        @media (max-width: 440px) {

            .employee-section table,
            .employee-section tr {
                display: block;
                width: 100%;
            }

            .employee-section td {
                padding: 4px 0;
                text-align: left;
            }

            .employee-section td.employee-title {
                font-weight: 600;
                margin-top: 8px;
            }

            /* Optional: make a little gap between fields */
            .employee-section tr+tr {
                margin-top: 12px;
            }
               .employee-section td {

            font-size: 11px!important;
        }

        }



        /* Employee Info */
        .employee-section {
            padding: 24px;
            background: #f9fafb;
            border-bottom: 2px solid #d1d5db;
            width: 100%;
            border-collapse: collapse;
        }

        .employee-section h3 {
            font-size: 25px;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .employee-section table {
            width: 100%;
            border-collapse: collapse;
        }

        .employee-section td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }

        /* Period */
        .period-info-bar {
            display: flex;
            justify-content: space-between;
            border: 1px solid #d1d5db;
            padding: 10px 15px;
            margin-bottom: 20px;
        }

        .period-label {
            font-size: 11px;
            font-weight: 700;
        }

        .period-value {
            font-size: 13px;
        }

        /* Salary */
        .salary-breakdown {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .breakdown-card {
            border: 1px solid #d1d5db;
            padding: 16px;
        }

        .breakdown-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .breakdown-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
        }

        .breakdown-total {
            border-top: 1px solid #d1d5db;
            margin-top: 8px;
            padding-top: 8px;
            font-weight: 700;
        }

        /* Net Salary */
        .net-salary-section {
            border: 2px solid #0f766e;
            padding: 20px;
            text-align: center;
            margin: 24px 0;
        }

        .net-salary-label {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .net-salary-amount {
            font-size: 26px;
            font-weight: 800;
        }

        /* Payment */
        .payment-details-section {
            border: 1px solid #d1d5db;
            padding: 16px;
            margin-top: 20px;
        }

        .payment-title {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .payment-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
        }

        /* Action */
        .action-section {
            padding: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn-action {
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            color: #fff;
            background: #0f766e;
        }

        .btn-download {
            background: #374151
        }

        /* Footer */
        .payslip-footer {
            text-align: center;
            padding: 14px;
            font-size: 12px;
            color: #555;
            border-top: 1px solid #d1d5db;
        }

        .employee-title {
            font-size: 20px;
            font-weight: 600;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding: 0 20px;
        }

        .signature-box {
            width: 45%;
            text-align: center;
        }

        .signature-title {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 40px;
            /* space for signature */
        }

        .signature-line {
            border-bottom: 1px solid #111;
            height: 2px;
        }


        /* Responsive */
        @media(max-width:768px) {
            .salary-breakdown {
                grid-template-columns: 1fr
            }

            .period-info-bar {
                flex-direction: column;
                gap: 10px
            }
        }

        /* Print */
        @media print {

            body {
                font-family: Arial, Helvetica, sans-serif !important;
                background: #fff !important;

            }

            img {
                max-width: 160px;
                image-rendering: crisp-edges;
            }

            i,
            .fa,
            .fas,
            .far,
            .fab {
                display: none !important;
            }

            .action-section {
                display: none
            }

            .payslip-wrapper {
                border: none
            }


            .period-info-bar {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: center;
            }

            .period-item {
                display: flex;
                gap: 8px;
                width: 48%;
            }

            .period-text {
                display: flex;
                flex-direction: column;
            }

            .salary-breakdown {
                display: flex !important;
                flex-direction: row !important;
                gap: 16px;
            }

            .breakdown-card {
                width: 50%;
                page-break-inside: avoid;
            }
        }
    </style>

</head>

<body>
    <div class="payslip-wrapper" id="payslip">

        <!-- Payslip Header Section -->

        <div class="company-header">
            <div class="header-content">
                <div style="flex: 1;">
                    <div class="employee-header">
                        <h2 class="payslip-main-title">EMPLOYEE PAYSLIP</h2>
                        <h3 class="company-title">Details of the issuing company</h3>
                        <?php if (getSetting('logo')): ?>
                            <img src="<?= getImage('settings', getSetting('logo')); ?>" alt="Company Logo" style="max-width: 185px; margin-bottom: 20px;">

                            <div class="employee-info-value"><?= getSetting('email') ?></div>
                            <div class="employee-info-value"><?= getSetting('phone') ?></div>
                        <?php endif; ?>

                    </div>
                    <p class="payslip-id">Payslip Number: MZ-<?= $payslip['id'] ?></p>

                </div>
            </div>
        </div>

        <!-- Employee Information Section (Bottom) -->
        <div class="employee-section">
            <h3>Employee Information</h3>
            <table class="employee-table">
                <tr>
                    <td class="employee-title"><strong>Name</strong></td>
                    <td><?= $payslip['employee_name'] ?></td>
                    <td class="employee-title"><strong>Designation</strong></td>
                    <td><?= $payslip['designation_name'] ?></td>
                </tr>
                <tr>
                    <td class="employee-title"><strong>Department</strong></td>
                    <td><?= $payslip['department_name'] ?></td>
                    <td class="employee-title"><strong>Phone</strong></td>
                    <td><?= $payslip['phone'] ?></td>
                </tr>

            </table>
        </div>


        <!-- Main Content Section -->
        <div class="payslip-content">

            <!-- Period Information Bar -->
            <div class="period-info-bar">
                <div class="period-item">
                    <div class="period-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="period-text">
                        <span class="period-label">Created Date</span>
                        <span class="period-value"><?= $payslip['created_at'] ?></span>
                    </div>
                </div>

                <div class="period-item">
                    <div class="period-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="period-text">
                        <span class="period-label">Date Due</span>
                        <span class="period-value"><?= $payslip['updated_at'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Salary Breakdown -->
            <div class="salary-breakdown">

                <!-- Earnings Card -->
                <div class="breakdown-card">
                    <div class="breakdown-header">
                        <div class="breakdown-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <h3 class="breakdown-title">Earnings</h3>
                    </div>

                    <div class="breakdown-item">
                        <span class="item-name">Basic Salary</span>
                        <span class="item-amount earning">
                            <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                            <?= number_format($payslip['basic_salary'], 2) ?>
                        </span>
                    </div>

                    <?php $earningTotal = $payslip['basic_salary']; ?>
                    <?php foreach ($payslip['items'] as $item): ?>
                        <?php if ($item['item_type'] === 'earning'): ?>
                            <?php $earningTotal += $item['amount']; ?>
                            <div class="breakdown-item">
                                <span class="item-name"><?= $item['item_name'] ?></span>
                                <span class="item-amount earning ">
                                    <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                                    <?= number_format($item['amount'], 2) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <div class="breakdown-total">
                        <span class="total-label fs-6 fw-semibold">Total Earnings</span>
                        <span class="total-amount fs-6 fw-semibold earning text-success">
                            <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                            <?= number_format($earningTotal, 2) ?>
                        </span>
                    </div>
                </div>

                <!-- Deductions Card -->
                <?php
                $deductionTotal = 0;
                $hasDeductions = false;
                foreach ($payslip['items'] as $item) {
                    if ($item['item_type'] === 'deduction') {
                        $hasDeductions = true;
                        $deductionTotal += $item['amount'];
                    }
                }
                ?>

                <div class="breakdown-card">
                    <div class="breakdown-header">
                        <div class="breakdown-icon deduction">
                            <i class="fas fa-minus-circle"></i>
                        </div>
                        <h3 class="breakdown-title">Deductions</h3>
                    </div>

                    <?php if ($hasDeductions): ?>
                        <?php foreach ($payslip['items'] as $item): ?>
                            <?php if ($item['item_type'] === 'deduction'): ?>
                                <div class="breakdown-item">
                                    <span class="item-name"><?= $item['item_name'] ?></span>
                                    <span class="item-amount deduction">
                                        <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                                        <?= number_format($item['amount'], 2) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="breakdown-item">
                            <span class="item-name">No Deductions</span>
                            <span class="item-amount deduction">
                                <i class="fa-solid fa-bangladeshi-taka-sign"></i> 0.00
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="breakdown-total">
                        <span class="total-label fs-6 fw-semibold">Total Deductions</span>
                        <span class="fs-6 total-amount fw-semibold deduction text-danger">
                            <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                            <?= number_format($deductionTotal, 2) ?>
                        </span>
                    </div>
                </div>

            </div>

            <!-- Net Salary Section -->
            <div class="net-salary-section">
                <div class="net-salary-content">
                    <div class="net-salary-label">Net Salary (Take Home)</div>
                    <div class="net-salary-amount">
                        <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                        <?= number_format($payslip['net_salary'], 2) ?>
                    </div>

                    <div class="amount-in-words">
                        <div class="words-label">Amount in Words</div>
                        <div class="words-text">
                            <?= amountInWords($payslip['net_salary']) ?> Taka Only
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="payment-details-section">
                <?php if ($payslip['status'] === 'paid'): ?>
                    <h4 class="payment-title">
                        <i class="fas fa-credit-card"></i> Payment Details
                    </h4>

                    <div class="payment-grid">
                        <div class="payment-item">
                            <span class="payment-label">Payment Method:</span>
                            <span class="payment-value"><?= ucfirst($payslip['payment_method']) ?></span>
                        </div>

                        <?php if ($payslip['payment_method'] === 'bank'): ?>
                            <div class="payment-item">
                                <span class="payment-label">Bank Name:</span>
                                <span class="payment-value"><?= htmlspecialchars($payslip['bank_name'] ?? '-') ?></span>
                            </div>
                            <div class="payment-item">
                                <span class="payment-label">Account Name:</span>
                                <span class="payment-value"><?= htmlspecialchars($payslip['account_name'] ?? '-') ?></span>
                            </div>
                            <div class="payment-item">
                                <span class="payment-label">Account Number:</span>
                                <span class="payment-value"><?= htmlspecialchars($payslip['account_number'] ?? '-') ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="payment-item">
                            <span class="payment-label">Status:</span>
                            <span class="payment-value">
                                <span class="status-badge status-paid">
                                    <i class="fas fa-check-circle"></i> PAID
                                </span>
                            </span>
                        </div>
                    </div>

                <?php else: ?>
                    <h4 class="payment-title">
                        <i class="fas fa-exclamation-circle"></i> Payment Status
                    </h4>
                    <div style="text-align: center;">
                        <span class="status-badge status-pending">
                            <i class="fas fa-clock"></i> PENDING
                        </span>
                    </div>
                <?php endif; ?>
            </div>

        </div>




        <!-- Action Buttons -->
        <div class="action-section">
            <button type="button" class="btn-action btn-print" onclick="printPayslip()">
                <i class="fas fa-print"></i> Print Payslip
            </button>


            <!-- <a href="<= $GLOBALS['base_url'] ?>/download-payslip/<= $payslip['id'] ?>" target="_blank" class="btn-action btn-download">
                <i class="fas fa-file-pdf"></i> Download PDF
            </a> -->
            <button type="button" class="btn-action btn-download" onclick="downloadPayslipPDF()">
                <i class="mdi mdi-download"></i> Download PDF
            </button>

        </div>

        <!-- Footer -->
        <div class="payslip-footer">
            <!-- <p><i class="fas fa-info-circle"></i> This is a computer-generated payslip. No signature is required.</p>
    <p>For any queries, please contact the HR Department</p> -->

            <!-- Signature Section -->
            <div class="signature-section">
                <div class="signature-box">
                    <span class="signature-title">Employee Signature</span>
                    <div class="signature-line"></div>
                </div>
                <div class="signature-box">
                    <span class="signature-title">Authorized Signature</span>
                    <div class="signature-line"></div>
                </div>
            </div>
        </div>

    </div>
    <script>
        function printPayslip() {
            window.print();
        }
         // Auto-print if ?print=1 is in URL
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('print') === '1') {
                printPayslip(); // triggers browser print dialog immediately
            }
        });
    </script>

    <script>
        function loadScript(src) {
            return new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = src;
                s.onload = resolve;
                s.onerror = reject;
                document.body.appendChild(s);
            });
        }

        async function downloadPayslipPDF() {

            if (typeof html2canvas === 'undefined') {
                await loadScript('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js');
            }
            if (typeof window.jspdf === 'undefined') {
                await loadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js');
            }

            const {
                jsPDF
            } = window.jspdf;
            const payslip = document.getElementById('payslip');

            const canvas = await html2canvas(payslip, {
                scale: 2
            });
            const imgData = canvas.toDataURL('image/png');

            const pdf = new jsPDF('p', 'mm', 'a4');
            const width = 210;
            const height = (canvas.height * width) / canvas.width;

            pdf.addImage(imgData, 'PNG', 0, 0, width, height);
            pdf.save('Payslip.pdf');
        }
    </script>



</body>

</html>