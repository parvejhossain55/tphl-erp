<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - <?= htmlspecialchars($payslip['employee_name']) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* ================= BASE ================= */
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

        .payslip-wrapper {
            max-width: 900px;
            margin: auto;
            background: #fff;
            border: 1px solid #d1d5db;
        }

        /* ================= HEADER ================= */
        .company-header {
            display: flex;
            justify-content: space-between;
            gap: 30px;
            padding: 24px;
            border-bottom: 3px solid #0f766e;
        }

        .header-block {
            max-width: 50%
        }

        .company-logo {
            max-height: 60px;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 18px;
            font-weight: 700;
            color: #0f766e;
        }

        .company-info {
            font-size: 14px;
            line-height: 1.6;
        }

        .header-right {
            text-align: right;
            font-size: 14px;
        }

        .employee-row {
            margin-bottom: 6px;
        }

        .employee-row span {
            display: inline-block;
            min-width: 100px;
            font-weight: 700;
            font-size: 16px;
            color: #171b22ff;
        }

        /* ================= TITLE ================= */
        .payslip-main-title {
            text-align: center;
            font-size: 26px;
            font-weight: 800;
            margin: 20px 0 12px;
        }

        .payslip-main-title::after {
            content: "";
            display: block;
            width: 90px;
            height: 3px;
            background: #0f766e;
            margin: 8px auto 0;
        }

        /* ================= PERIOD BAR ================= */
        .period-info-bar {
            margin: 0 24px 20px;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }

        .period-label {
            font-weight: 700;
            color: #0f766e;
        }

        /* ================= CONTENT ================= */
        .payslip-content {
            padding: 24px
        }

        /* ================= SALARY ================= */
        .salary-breakdown {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .breakdown-card {
            border: 1px solid #d1d5db;
            padding: 18px;
        }

        .breakdown-title {
            font-size: 16px;
            font-weight: 700;
            color: #0f766e;
            margin-bottom: 12px;
        }

        .breakdown-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
        }

        .breakdown-total {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #d1d5db;
            font-weight: 700;
        }

        /* ================= NET SALARY ================= */
        .net-salary-section {
            margin: 24px 0;
            padding: 22px;
            border: 2px solid #0f766e;
            text-align: center;
        }

        .net-salary-label {
            font-size: 15px;
            font-weight: 700;
            color: #0f766e;
        }

        .net-salary-amount {
            font-size: 28px;
            font-weight: 800;
            margin: 6px 0;
        }

        .words-label {
            font-weight: 700;
            color: #0f766e;
        }

        /* ================= PAYMENT ================= */
        .payment-details-section {
            border: 1px solid #d1d5db;
            padding: 18px;
        }

        .payment-title {
            font-size: 16px;
            font-weight: 700;
            color: #0f766e;
            margin-bottom: 12px;
        }

        .payment-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
        }

        .payment-label {
            font-weight: 600;
        }

        /* ================= ACTION ================= */
        .action-section {
            padding: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn-action {
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            border: none;
            background: #0f766e;
        }

        .btn-download {
            background: #374151
        }

        /* ================= FOOTER ================= */
        .payslip-footer {
            border-top: 1px solid #d1d5db;
            padding: 20px;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .signature-box {
            width: 45%;
            text-align: center
        }

        .signature-title {
            font-weight: 600;
            margin-bottom: 40px;
        }

        .signature-line {
            border-bottom: 1px solid #111
        }

        .payslip-status-wrapper {
            margin-top: 14px;
            text-align: center;
        }

        .status-btn {
            display: inline-block;
            padding: 8px 26px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 20px;
            letter-spacing: 1px;
            border: 2px solid transparent;
        }

        .status-paid {
            background: #dcfce7;
            color: #166534;
            border-color: #22c55e;
        }

        .status-unpaid {
            background: #fee2e2;
            color: #991b1b;
            border-color: #ef4444;
        }

        /* ================= RESPONSIVE ================= */
        @media (max-width: 768px) and (min-width: 570px) {
            .company-header {
                display: flex;
                flex-direction: row;
                /* পাশাপাশি থাকবে */
                align-items: flex-start;
                justify-content: space-between;
                gap: 10px;
            }

            .header-block {
                width: 50%;
            }

            /* LOGO SMALLER */
            .company-logo {
                max-height: 40px;
                /* আগের চেয়ে ছোট */
                margin-bottom: 4px;
            }

            /* COMPANY INFO */
            .company-info {
                font-size: 11px;
                line-height: 1.2;
            }

            .company-name {
                font-size: 13px;
                font-weight: 700;
                margin-bottom: 2px;
            }

            /* EMPLOYEE INFO */
            .header-right {
                text-align: right;
            }

            .employee-row {
                font-size: 11px;
                line-height: 1.25;
                white-space: nowrap;
                /* ভাঙবে না */
            }




            .salary-breakdown {
                grid-template-columns: 1fr
            }

            .period-info-bar {
                flex-direction: column;
                gap: 8px
            }
        }



        /* ================= EXTRA SMALL DEVICES ================= */
        @media (max-width: 560px) {

            .company-header {
                flex-direction: column;
                align-items: flex-start;
                /* left align */
                gap: 6px;
            }

            .employee-row span {
                font-size: 12px;
            }

            .breakdown-item {
                font-size: 12px;
            }

            .header-block {
                width: 100%;
                /* পুরো width নেবে */
                display: flex;
                flex-direction: column;
                /* logo + company info vertical */
                align-items: flex-start;
            }

            .company-logo {
                max-height: 32px;
                margin-bottom: 4px;
            }

            .company-info,
            .employee-row {
                font-size: 10px;
                line-height: 1.2;
                white-space: normal;
                /* text wrap হবে */
            }

            .company-name {
                font-size: 12px;
                font-weight: 700;
                margin-bottom: 2px;
            }

            .header-right {
                width: 100%;
                text-align: left;
                display: flex;
                flex-direction: column;
                /* employee info vertical */
                gap: 2px;
                margin-top: 6px;
            }

            .period-info-bar {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                gap: 12px !important;
            }

            .period-info-bar>div {
                font-size: 10px;
            }

            .payslip-main-title {
                font-size: 18px;
            }

        }


        /* ================= PRINT ================= */
        @media print {

            @page {
                size: A4;
                margin: 8mm;
            }

            body {
                background: #fff !important;
                font-size: 11px;
                line-height: 1.3;
            }

            /* ================= HEADER FIX ================= */

            .company-header {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
                gap: 20px !important;
            }

            .header-block {
                width: 48% !important;
                max-width: 48% !important;
            }

            .header-right {
                text-align: right !important;
            }

            /* ================= TITLE ================= */

            .payslip-main-title {
                font-size: 22px !important;
                margin: 12px 0 !important;
            }

            /* ================= PERIOD / DATE FIX ================= */

            .period-info-bar {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                gap: 12px !important;
            }

            .period-info-bar>div {
                width: 32% !important;
                text-align: left;
                white-space: nowrap;
            }

            .company-logo {
                height: 40px;
            }

            /* ================= SALARY CARDS FIX ================= */

            .salary-breakdown {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                gap: 12px !important;
            }

            .breakdown-card {
                width: 50% !important;
                max-width: 50% !important;
                border: 1px solid #999 !important;
                padding: 12px !important;
            }

            .status-btn {
                font-size: 12px;
                padding: 6px 20px;
            }

            /* ================= NET SALARY ================= */

            .net-salary-section {
                padding: 14px !important;
                margin: 14px 0 !important;
            }

            .net-salary-amount {
                font-size: 22px !important;
            }

            /* ================= PAYMENT ================= */

            .payment-details-section {
                padding: 12px !important;
                margin-top: 12px !important;
            }

            /* ================= FOOTER ================= */

            .signature-section {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                gap: 20px !important;
            }

            .signature-box {
                width: 45% !important;
            }

            /* ================= HIDE UI ================= */

            .action-section {
                display: none !important;
            }

            * {
                box-shadow: none !important;
                animation: none !important;
                transition: none !important;
                page-break-inside: avoid !important;
            }
        }
    </style>
</head>

<body>

    <div class="payslip-wrapper" id="payslip">

        <!-- HEADER -->
        <div class="company-header">
            <div class="header-block">
                <?php if (getSetting('logo')): ?>
                    <img src="<?= getImage('settings', getSetting('logo')) ?>" class="company-logo">
                <?php endif; ?>

                <div class="company-info">
                    <div class="company-name"><?= getSetting('wesite_title') ?></div>
                    <div><?= getSetting('email') ?></div>
                    <div><?= getSetting('phone') ?></div>
                </div>
            </div>

            <div class="header-block header-right">
                <div class="employee-row"><span class="pe-2">Employee: </span><?= htmlspecialchars($payslip['employee_name']) ?></div>
                <div class="employee-row "><span class="pe-2">Designation: </span><?= htmlspecialchars($payslip['designation_name']) ?></div>
                <div class="employee-row"><span class="pe-2">Department: </span><?= htmlspecialchars($payslip['department_name']) ?></div>
            </div>
        </div>

        <!-- TITLE -->
        <div class="payslip-main-title">
            Payslip of <?= date('F Y', strtotime($payslip['created_at'])) ?>
        </div>

        <!-- PERIOD -->
        <div class="period-info-bar">
            <div><span class="period-label">Created:</span> <?= date('d M Y', strtotime($payslip['created_at'])) ?></div>
            <div><span class="period-label">Due:</span> <?= date('d M Y', strtotime($payslip['updated_at'])) ?></div>
            <div><span class="period-label">Payslip No:</span> PS-<?= $payslip['id'] ?></div>
        </div>

        <div class="payslip-content">

            <!-- SALARY -->
            <div class="salary-breakdown">

                <!-- Earnings -->
                <div class="breakdown-card">
                    <div class="breakdown-title">Earnings</div>

                    <div class="breakdown-item">
                        <span>Basic Salary</span>
                        <span>৳ <?= number_format($payslip['basic_salary'], 2) ?></span>
                    </div>

                    <?php $earningTotal = $payslip['basic_salary']; ?>
                    <?php foreach ($payslip['items'] as $item): ?>
                        <?php if ($item['item_type'] === 'earning'): ?>
                            <?php $earningTotal += $item['amount']; ?>
                            <div class="breakdown-item">
                                <span><?= htmlspecialchars($item['item_name']) ?></span>
                                <span>৳ <?= number_format($item['amount'], 2) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <div class="breakdown-total">
                        <span>Total Earnings</span>
                        <span>৳ <?= number_format($earningTotal, 2) ?></span>
                    </div>
                </div>

                <!-- Deductions -->
                <div class="breakdown-card">
                    <div class="breakdown-title">Deductions</div>

                    <?php
                    $deductionTotal = 0;
                    $hasDeduction = false;
                    foreach ($payslip['items'] as $item) {
                        if ($item['item_type'] === 'deduction') {
                            $hasDeduction = true;
                            $deductionTotal += $item['amount'];
                        }
                    }
                    ?>

                    <?php if ($hasDeduction): ?>
                        <?php foreach ($payslip['items'] as $item): ?>
                            <?php if ($item['item_type'] === 'deduction'): ?>
                                <div class="breakdown-item">
                                    <span><?= htmlspecialchars($item['item_name']) ?></span>
                                    <span>৳ <?= number_format($item['amount'], 2) ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="breakdown-item">
                            <span>No Deduction</span>
                            <span>৳ 0.00</span>
                        </div>
                    <?php endif; ?>

                    <div class="breakdown-total">
                        <span>Total Deductions</span>
                        <span>৳ <?= number_format($deductionTotal, 2) ?></span>
                    </div>
                </div>

            </div>
            <!-- NET -->
            <div class="net-salary-section">
                <div class="net-salary-label">Net Salary (Take Home)</div>

                <div class="net-salary-amount">
                    ৳ <?= number_format($payslip['net_salary'], 2) ?>
                </div>

                <div class="amount-words">
                    <span class="words-label">Amount in Words:</span>
                    <?= amountInWords($payslip['net_salary']) ?> Taka Only
                </div>

                <!-- STATUS BUTTON -->
                <div class="payslip-status-wrapper">
                    <?php if ($payslip['status'] === 'paid'): ?>
                        <span class="status-btn status-paid">
                            ✔ PAID
                        </span>

                    <?php endif; ?>
                </div>
            </div>


            <!-- PAYMENT -->
            <div class="payment-details-section">
                <?php if ($payslip['status'] === 'paid'): ?>
                    <div class="payment-title">Payment Details</div>

                    <div class="payment-item">
                        <span class="payment-label">Payment Method</span>
                        <span><?= ucfirst($payslip['payment_method']) ?></span>
                    </div>

                    <?php if ($payslip['payment_method'] === 'bank'): ?>
                        <div class="payment-item"><span class="payment-label">Bank Name</span><?= htmlspecialchars($payslip['bank_name'] ?? '-') ?></div>
                        <div class="payment-item"><span class="payment-label">Account Number</span><?= htmlspecialchars($payslip['account_number'] ?? '-') ?></div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="payslip-status-wrapper">
                        <p class="fw-semibold fs-6">Payment Status</p>
                        <span class="status-btn status-unpaid">
                            ✖ UNPAID
                        </span>
                    </div>
                <?php endif; ?>
            </div>

        </div>
          <!-- Footer -->
        <div class="payslip-footer">
            <!-- <p><i class="fas fa-info-circle"></i> This is a computer-generated payslip. No signature is required.</p>
    <p>For any queries, please contact the HR Department</p> -->

            <!-- Signature Section -->
            <div class="signature-section">
                <div class="signature-box">
                    <span class="signature-title ">Employee Signature</span>
                    <div class="signature-line mt-5"></div>
                </div>
                <div class="signature-box">
                    <span class="signature-title">Authorized Signature</span>
                    <div class="signature-line mt-5"></div>
                </div>
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