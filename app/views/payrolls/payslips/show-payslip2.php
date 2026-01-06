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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box}

body{
    font-family:Poppins,sans-serif;
    background:#ffffff;
    color:#111827;
    padding:24px;
    font-size:14px;
}

/* Wrapper */
.payslip-wrapper{
    max-width:900px;
    margin:auto;
    border:1px solid #d1d5db;
}

/* Header */
.company-header{
    padding:20px;
    border-bottom:2px solid #d1d5db;
    text-align:center;
}
.payslip-main-title{
    font-size:20px;
    font-weight:700;
    letter-spacing:1px;
}
.payslip-id{
    margin-top:6px;
    font-size:13px;
    color:#6b7280;
}

/* Company Info */
.employee-header img{max-height:80px;margin:12px 0}
.employee-info-value{font-size:13px;color:#374151}

/* Section */
.payslip-content{padding:24px}
.section-title{
    font-size:16px;
    font-weight:700;
    border-bottom:2px solid #d1d5db;
    margin-bottom:12px;
    padding-bottom:6px;
}

/* Employee Card */
.employee-section{
    padding:24px;
    background:#f9fafb;
    border-bottom:2px solid #d1d5db;
}
.employee-card{
    border:1px solid #d1d5db;
    padding:16px;
    background:#ffffff;
}
.employee-row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
}
.employee-item{
    display:flex;
    gap:8px;
}
.employee-label{
    font-size:12px;
    color:#6b7280;
    width:120px;
}
.employee-value{
    font-weight:600;
    color:#111827;
}

/* Period */
.period-info-bar{
    display:flex;
    justify-content:space-between;
    border:1px solid #d1d5db;
    padding:12px;
    margin-bottom:24px;
}
.period-label{font-size:12px;color:#6b7280}
.period-value{font-weight:600}

/* Salary */
.salary-breakdown{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
}
.breakdown-card{
    border:1px solid #d1d5db;
    padding:16px;
}
.breakdown-title{
    font-weight:700;
    margin-bottom:12px;
}
.breakdown-item{
    display:flex;
    justify-content:space-between;
    padding:6px 0;
}
.total{
    border-top:1px solid #d1d5db;
    margin-top:8px;
    padding-top:8px;
    font-weight:700;
}

/* Net Salary */
.net-salary-section{
    margin-top:24px;
    border:2px solid #111827;
    padding:20px;
    text-align:center;
}
.net-salary-label{
    font-size:13px;
    color:#6b7280;
}
.net-salary-amount{
    font-size:28px;
    font-weight:800;
    margin-top:6px;
}

/* Payment */
.payment-details-section{
    margin-top:24px;
    border:1px solid #d1d5db;
    padding:16px;
}
.payment-item{
    display:flex;
    justify-content:space-between;
    padding:6px 0;
}

/* Action */
.action-section{
    padding:16px;
    display:flex;
    justify-content:flex-end;
    gap:12px;
}
.btn-action{
    padding:10px 18px;
    border:1px solid #111827;
    background:#111827;
    color:#fff;
    font-size:13px;
    cursor:pointer;
}
.btn-action.secondary{
    background:#ffffff;
    color:#111827;
}

/* Footer */
.payslip-footer{
    padding:16px;
    text-align:center;
    font-size:12px;
    color:#6b7280;
    border-top:1px solid #d1d5db;
}

/* Print */
@media print{
    body{padding:0}
    .action-section{display:none}
    .payslip-wrapper{border:none}
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
                            <div class="employee-info-value"><?= getSetting('website_title') ?></div>
                            <div class="employee-info-value"><?= getSetting('address') ?></div>
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
    <div class="section-title">Employee Information</div>

    <div class="employee-card">
        <div class="employee-row">
            <div class="employee-item">
                <div class="employee-label">Employee Name</div>
                <div class="employee-value"><?= $payslip['employee_name'] ?></div>
            </div>
            <div class="employee-item">
                <div class="employee-label">Designation</div>
                <div class="employee-value"><?= $payslip['designation_name'] ?></div>
            </div>
            <div class="employee-item">
                <div class="employee-label">Department</div>
                <div class="employee-value"><?= $payslip['department_name'] ?></div>
            </div>
            <div class="employee-item">
                <div class="employee-label">Phone</div>
                <div class="employee-value"><?= $payslip['phone'] ?></div>
            </div>
            <div class="employee-item">
                <div class="employee-label">Email</div>
                <div class="employee-value"><?= $payslip['email'] ?></div>
            </div>
        </div>
    </div>
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
                                <span class="item-amount earning">
                                    <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                                    <?= number_format($item['amount'], 2) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <div class="breakdown-total">
                        <span class="total-label">Total Earnings</span>
                        <span class="total-amount earning">
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
                        <span class="total-label">Total Deductions</span>
                        <span class="total-amount deduction">
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
            <button target="_blank" class="btn-action btn-download" onclick="downloadPayslipPDF()">
                <i class="mdi mdi-download"></i> Download PDF
            </button>
        </div>

        <!-- Footer -->
        <div class="payslip-footer">
            <p><i class="fas fa-info-circle"></i> This is a computer-generated payslip. No signature is required.</p>
            <p>For any queries, please contact the HR Department</p>
        </div>

    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>



    <script>
        function printPayslip() {
            window.print();
        }
        async function downloadPayslipPDF() {
            const {
                jsPDF
            } = window.jspdf;
            const payslip = document.querySelector("#payslip");

            try {
                const canvas = await html2canvas(payslip, {
                    scale: 2, // higher scale for sharp text
                    useCORS: true
                });

                const imgData = canvas.toDataURL("image/png");

                const pdf = new jsPDF("p", "mm", "a4");
                const pdfWidth = 210;
                const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

                let heightLeft = pdfHeight;
                let position = 0;

                pdf.addImage(imgData, "PNG", 0, position, pdfWidth, pdfHeight);
                heightLeft -= 297; // A4 page height in mm

                while (heightLeft > 0) {
                    position = heightLeft - pdfHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, "PNG", 0, position, pdfWidth, pdfHeight);
                    heightLeft -= 297;
                }

                pdf.save("Payslip_<php echo $payslip['employee_name']; ?>.pdf");
            } catch (error) {
                alert("Error generating PDF. Check console for details.");
                console.error(error);
            }
        }
    </script>

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


</body>

</html>


<!-- 1st design slip  -->
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-green: #059669;
            --primary-dark: #047857;
            --primary-light: #10b981;
            --accent-green: #34d399;
            --success-green: #22c55e;
            --dark-green: #065f46;
            --light-green-bg: #ecfdf5;
            --danger-red: #ef4444;
            --warning-amber: #f59e0b;
            --dark-text: #1f2937;
            --gray-text: #6b7280;
            --border-color: #d1fae5;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
           background: linear-gradient(135deg, #e5e9e7 0%, #eff3f1 100%);
            padding: 40px 20px;
            min-height: 100vh;
            color: var(--dark-text);
        }

        .payslip-wrapper {
            max-width: 1100px;
            margin: 0 auto;
            background: var(--white);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 70px rgba(5, 150, 105, 0.15);
        }

        /* Company Header Section */
        .company-header {
            /* background: linear-gradient(135deg, #21b385 0%, #62e3bf 100%); */
            padding: 10PX;
            position: relative;
            overflow: hidden;
        }

        .company-header::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .company-header::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .header-content {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .company-info-section {
            flex: 1;
        }

        .company-logo {
            max-width: 185px;
            margin-bottom: 25px;
            filter: brightness(0) invert(1);
        }

        .company-name {
            font-family: 'Montserrat', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .company-details {
            color: rgba(255, 255, 255, 0.95);
            font-size: 15px;
            line-height: 1.8;
            font-weight: 400;
        }

        .company-details i {
            margin-right: 10px;
            width: 20px;
            color: var(--accent-green);
        }

        .payslip-title-section {
            text-align: center;
            color: var(--white);
            width: 100%;
        }

        .payslip-main-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 3px;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .payslip-id {
            font-size: 18px;
            opacity: 0.95;
            font-weight: 500;
            letter-spacing: 1.5px;
        }

        /* Main Content Section */
        .payslip-content {
            padding: 50px 60px;
        }

        /* Period Info Bar */
        .period-info-bar {
            background: linear-gradient(135deg, var(--light-green-bg) 0%, #d1fae5 100%);
            padding: 25px 35px;
            border-radius: 16px;
            border-left: 6px solid var(--primary-green);
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .period-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .period-icon {
            width: 45px;
            height: 45px;
            background: var(--primary-green);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 18px;
        }

        .period-text {
            display: flex;
            flex-direction: column;
        }

        .period-label {
            font-size: 12px;
            color: var(--gray-text);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .period-value {
            font-size: 15px;
            color: var(--dark-text);
            font-weight: 700;
        }

        /* Salary Breakdown Section */
        .salary-breakdown {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .breakdown-card {
            background: var(--white);
            border-radius: 20px;
            padding: 35px;
            border: 2px solid var(--border-color);
            box-shadow: 0 8px 30px rgba(5, 150, 105, 0.08);
            transition: all 0.3s ease;
        }

        .breakdown-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 45px rgba(5, 150, 105, 0.15);
        }

        .breakdown-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid var(--border-color);
        }

        .breakdown-icon {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, var(--primary-green), var(--accent-green));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 24px;
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.3);
        }

        .breakdown-icon.deduction {
            background: linear-gradient(135deg, var(--danger-red), #dc2626);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }

        .breakdown-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: var(--dark-text);
            margin: 0;
        }

        .breakdown-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .breakdown-item:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: 600;
            color: var(--dark-text);
            font-size: 15px;
        }

        .item-amount {
            font-weight: 700;
            font-size: 16px;
        }

        .item-amount.earning {
            color: var(--primary-green);
        }

        .item-amount.deduction {
            color: var(--danger-red);
        }

        .breakdown-total {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 3px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-label {
            font-family: 'Montserrat', sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: var(--dark-text);
        }

        .total-amount {
            font-family: 'Montserrat', sans-serif;
            font-size: 22px;
            font-weight: 800;
        }

        .total-amount.earning {
            color: var(--primary-green);
        }

        .total-amount.deduction {
            color: var(--danger-red);
        }

        /* Net Salary Card */
        .net-salary-section {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            border-radius: 24px;
            padding: 45px;
            text-align: center;
            margin: 9px 0;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(5, 150, 105, 0.3);
        }

        .net-salary-section::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .net-salary-section::after {
            content: '';
            position: absolute;
            bottom: -40px;
            left: -40px;
            width: 180px;
            height: 180px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .net-salary-content {
            position: relative;
            z-index: 2;
        }

        .net-salary-label {
            font-family: 'Montserrat', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 7px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .net-salary-amount {
            font-family: 'Montserrat', sans-serif;
            font-size: 43px;
            font-weight: 900;
            color: var(--white);
            letter-spacing: 2px;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .net-salary-amount i {
            font-size: 48px;
        }

        .amount-in-words {
            margin-top: 20px;
            padding: 20px 30px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .words-label {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .words-text {
            font-size: 18px;
            color: var(--white);
            font-weight: 600;
            text-transform: capitalize;
        }

        /* Payment Details */
        .payment-details-section {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-radius: 20px;
            padding: 35px;
            border-left: 6px solid var(--warning-amber);
            margin: 40px 0;
        }

        .payment-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: var(--dark-text);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .payment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .payment-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .payment-label {
            font-weight: 600;
            color: var(--gray-text);
            font-size: 14px;
        }

        .payment-value {
            font-weight: 700;
            color: var(--dark-text);
            text-align: right;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .status-paid {
            background: linear-gradient(135deg, var(--primary-green), var(--accent-green));
            color: var(--white);
            box-shadow: 0 4px 15px rgba(5, 150, 105, 0.3);
        }

        .status-pending {
            background: linear-gradient(135deg, var(--danger-red), #dc2626);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        /* Employee Information Section (Bottom) */
        .employee-section {
            background: linear-gradient(135deg, var(--light-green-bg) 0%, #d1fae5 100%);
            padding: 45px 60px;
            /* margin-top: 50px; */
            border-top: 4px solid var(--primary-green);
        }

        .employee-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .company-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 25px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }

        .employee-subtitle {
            color: var(--gray-text);
            font-size: 15px;
            font-weight: 500;
        }

        .employee-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .employee-card {
            background: var(--white);
            border-radius: 16px;
            padding: 25px 30px;
            box-shadow: 0 4px 20px rgba(5, 150, 105, 0.08);
            transition: all 0.3s ease;
        }

        .employee-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(5, 150, 105, 0.15);
        }

        .employee-info-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .employee-info-item:last-child {
            border-bottom: none;
        }

        .employee-info-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--primary-green), var(--accent-green));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 16px;
            flex-shrink: 0;
        }

        .employee-info-text {
            flex: 1;
        }

        .employee-info-label {
            font-size: 12px;
            color: var(--gray-text);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .employee-info-value {
            font-size: 15px;
            color: var(--dark-text);
            font-weight: 700;
        }

        /* Action Buttons */
        .action-section {
            padding: 40px 60px;
            background: var(--white);
            border-top: 2px solid var(--border-color);
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 16px 40px;
            border-radius: 14px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
            letter-spacing: 0.5px;
        }

        .btn-print {
            background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
            color: var(--white);
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.3);
        }

        .btn-print:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(5, 150, 105, 0.4);
        }

        .btn-download {
            background: linear-gradient(135deg, var(--warning-amber), #ea580c);
            color: var(--white);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.3);
        }

        .btn-download:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
            color: var(--white);
        }

        /* Footer */
        .payslip-footer {
            text-align: center;
            padding: 30px;
            background: var(--light-green-bg);
            color: var(--gray-text);
            font-size: 14px;
            line-height: 1.8;
        }

        .payslip-footer i {
            color: var(--primary-green);
            margin-right: 8px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 20px 10px;
            }

            .company-header,
            .payslip-content,
            .employee-section,
            .action-section {
                padding: 30px 25px;
            }

            .header-content {
                flex-direction: column;
                gap: 30px;
            }

            .payslip-title-section {
                text-align: left;
            }

            .company-name {
                font-size: 26px;
            }

            .payslip-main-title {
                font-size: 28px;
            }

            .salary-breakdown {
                grid-template-columns: 1fr;
            }

            .net-salary-amount {
                font-size: 42px;
            }

            .employee-grid {
                grid-template-columns: 1fr;
            }

            .period-info-bar {
                flex-direction: column;
                align-items: flex-start;
            }

            .action-section {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
            }
        }

        /* Print Styles */
        /* Print Styles */
        @media print {
            @page {
                size: A4;
                margin: 15mm;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color: #000 !important;
                /* Force all text to black */
                background: none !important;
                /* Remove background colors/gradients */
                box-shadow: none !important;
            }

            body {
                background: #fff !important;
                color: #000 !important;
                padding: 0 !important;
            }

            .payslip-wrapper {
                box-shadow: none !important;
                border-radius: 0 !important;
                background: #fff !important;
            }

            /* Remove gradients from sections */
            .company-header,
            .net-salary-section,
            .employee-section,
            .payment-details-section {
                background: #fff !important;
                color: #000 !important;
            }

            .breakdown-card {
                border: 1px solid #000 !important;
                /* Optional: simple border */
                background: #fff !important;
            }

            .employee-card {
                border: 1px solid #000 !important;
                background: #fff !important;
                box-shadow: none !important;
            }

            .breakdown-icon,
            .employee-info-icon,
            .status-badge {
                background: #000 !important;
                color: #fff !important;
                box-shadow: none !important;
            }

            /* Hide buttons */
            .action-section {
                display: none !important;
            }

            /* Grid adjustments for print */
            .salary-breakdown {
                display: block !important;
            }

            .employee-grid {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 20px !important;
            }

            /* Typography */
            h1,
            h2,
            h3,
            h4,
            h5,
            h6,
            p,
            span,
            div {
                color: #000 !important;
            }


      /* Date Info Box */
    .period-info-bar {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        border: 1px solid #000 !important;
        border-radius: 8px !important;
        padding: 10px 15px !important;
        margin-bottom: 20px !important;
        background: #fff !important;
        gap: 20px !important;
    }

    .period-item {
        display: flex !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 2px !important;
    }

    .period-label {
        font-weight: 700 !important;
        font-size: 12px !important;
        color: #000 !important;
        text-transform: uppercase;
    }

    .period-value {
        font-weight: 600 !important;
        font-size: 14px !important;
        color: #000 !important;
    }

    .period-icon {
        display: none !important;
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
                            <div class="employee-info-value"><?= getSetting('website_title') ?></div>
                            <div class="employee-info-value"><?= getSetting('address') ?></div>
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
            <div class="employee-header">
                <h3 class="employee-title">Employee Information</h3>
                <p class="employee-subtitle">Complete details of the payslip recipient</p>
            </div>

            <div class="employee-grid">
                <div class="employee-card">
                    <div class="employee-info-item">
                        <div class="employee-info-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="employee-info-text">
                            <div class="employee-info-label">Full Name</div>
                            <div class="employee-info-value"><?= $payslip['employee_name'] ?></div>
                        </div>
                    </div>

                    <div class="employee-info-item">
                        <div class="employee-info-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="employee-info-text">
                            <div class="employee-info-label">Position</div>
                            <div class="employee-info-value"><?= $payslip['designation_name'] ?></div>
                        </div>
                    </div>
                </div>

                <div class="employee-card">
                    <div class="employee-info-item">
                        <div class="employee-info-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="employee-info-text">
                            <div class="employee-info-label">Department</div>
                            <div class="employee-info-value"><?= $payslip['department_name'] ?></div>
                        </div>
                    </div>

                    <div class="employee-info-item">
                        <div class="employee-info-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="employee-info-text">
                            <div class="employee-info-label">Phone Number</div>
                            <div class="employee-info-value"><?= $payslip['phone'] ?></div>
                        </div>
                    </div>
                </div>

                <div class="employee-card">
                    <div class="employee-info-item">
                        <div class="employee-info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="employee-info-text">
                            <div class="employee-info-label">Email Address</div>
                            <div class="employee-info-value"><?= $payslip['email'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
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
                                <span class="item-amount earning">
                                    <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                                    <?= number_format($item['amount'], 2) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <div class="breakdown-total">
                        <span class="total-label">Total Earnings</span>
                        <span class="total-amount earning">
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
                        <span class="total-label">Total Deductions</span>
                        <span class="total-amount deduction">
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
            <button target="_blank" class="btn-action btn-download" onclick="downloadPayslipPDF()">
                <i class="mdi mdi-download"></i> Download PDF
            </button>
        </div>

        <!-- Footer -->
        <div class="payslip-footer">
            <p><i class="fas fa-info-circle"></i> This is a computer-generated payslip. No signature is required.</p>
            <p>For any queries, please contact the HR Department</p>
        </div>

    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>



    <script>
        function printPayslip() {
            window.print();
        }
        async function downloadPayslipPDF() {
            const {
                jsPDF
            } = window.jspdf;
            const payslip = document.querySelector("#payslip");

            try {
                const canvas = await html2canvas(payslip, {
                    scale: 2, // higher scale for sharp text
                    useCORS: true
                });

                const imgData = canvas.toDataURL("image/png");

                const pdf = new jsPDF("p", "mm", "a4");
                const pdfWidth = 210;
                const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

                let heightLeft = pdfHeight;
                let position = 0;

                pdf.addImage(imgData, "PNG", 0, position, pdfWidth, pdfHeight);
                heightLeft -= 297; // A4 page height in mm

                while (heightLeft > 0) {
                    position = heightLeft - pdfHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, "PNG", 0, position, pdfWidth, pdfHeight);
                    heightLeft -= 297;
                }

                pdf.save("Payslip_<php echo $payslip['employee_name']; ?>.pdf");
            } catch (error) {
                alert("Error generating PDF. Check console for details.");
                console.error(error);
            }
        }
    </script>

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


</body>

</html>