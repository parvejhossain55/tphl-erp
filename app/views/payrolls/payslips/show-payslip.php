<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Payslip - <?= $payslip['employee_name'] ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-dark: #4338ca;
            --secondary-color: #06b6d4;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --dark-color: #1f2937;
            --light-bg: #f9fafb;
            --border-color: #e5e7eb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
            padding: 30px 15px;
            min-height: 100vh;
            /* color: #333; */
        }

        .payslip-container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        /* Header Section */
        .payslip-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .payslip-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .payslip-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .payslip-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            text-align: center;
            letter-spacing: 1px;
        }

        .payslip-subtitle {
            text-align: center;
            font-size: 16px;
            opacity: 0.9;
            font-weight: 400;
        }

        /* Body Section */
        .payslip-body {
            padding: 40px;
        }

        /* Company & Employee Info Cards */
        .info-cards-wrapper {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }

        .info-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 15px;
            padding: 25px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .info-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #cbd5e1;
        }

        .info-card-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .info-card-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #64748b;
            font-size: 14px;
        }

        .info-value {
            font-weight: 600;
            color: var(--dark-color);
            text-align: right;
            font-size: 14px;
        }

        .company-logo {
            max-width: 150px;
            margin-bottom: 15px;
        }

        /* Payslip Tables */
        .payslip-table-wrapper {
            margin: 30px 0;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .payslip-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .payslip-table thead {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .payslip-table th {
            padding: 18px 20px;
            font-weight: 600;
            text-align: left;
            font-size: 15px;
            letter-spacing: 0.5px;
        }

        .payslip-table th:last-child,
        .payslip-table td:last-child {
            text-align: right;
        }

        .payslip-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.2s;
        }

        .payslip-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .payslip-table td {
            padding: 15px 20px;
            color: #475569;
            font-size: 14px;
        }

        .payslip-table td:first-child {
            font-weight: 600;
            color: var(--dark-color);
        }

        .amount-earning {
            color: var(--success-color);
            font-weight: 700;
        }

        .amount-deduction {
            color: var(--danger-color);
            font-weight: 700;
        }

        .total-row {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            font-weight: 700;
        }

        .total-row td {
            padding: 18px 20px;
            font-size: 16px;
            color: var(--dark-color);
        }

        /* Net Salary Section */
        .net-salary-card {
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
            color: white;
            padding: 35px;
            border-radius: 15px;
            text-align: center;
            margin: 35px 0;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
            position: relative;
            overflow: hidden;
        }

        .net-salary-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .net-salary-label {
            font-size: 18px;
            font-weight: 500;
            opacity: 0.95;
            margin-bottom: 12px;
            letter-spacing: 1px;
        }

        .net-salary-amount {
            font-size: 48px;
            font-weight: 800;
            letter-spacing: 1px;
            position: relative;
            z-index: 1;
        }

        /* Amount in Words */
        .amount-words-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 25px;
            border-radius: 15px;
            border-left: 5px solid var(--warning-color);
            margin: 25px 0;
        }

        .amount-words-label {
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 10px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .amount-words-value {
            color: #92400e;
            font-size: 18px;
            font-weight: 600;
            text-transform: capitalize;
            line-height: 1.6;
        }

        /* Payment Details */
        .payment-details-card {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            padding: 25px;
            border-radius: 15px;
            border-left: 5px solid var(--secondary-color);
            margin: 25px 0;
        }

        .payment-details-title {
            color: var(--dark-color);
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .status-paid {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
        }

        .status-pending {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            color: white;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            padding: 30px 40px;
            background: #f9fafb;
            border-top: 2px solid #e5e7eb;
        }

        .btn-action {
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: white;
        }

        .btn-print {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }

        .btn-print:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
        }

        .btn-download {
            background: linear-gradient(135deg, var(--warning-color), #ea580c);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        .btn-download:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
            color: white;
        }

        /* Footer */
        .payslip-footer {
            text-align: center;
            padding: 25px;
            background: #f9fafb;
            color: #64748b;
            font-size: 13px;
            line-height: 1.8;
        }

        .payslip-footer i {
            color: var(--primary-color);
            margin-right: 5px;
        }

        .divider {
            height: 2px;
            background: linear-gradient(to right, transparent, #e5e7eb, transparent);
            margin: 30px 0;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .payslip-container {
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
            }

            .action-buttons {
                display: none !important;
            }

            .payslip-body {
                padding: 20px;
            }

            .payslip-table tbody tr:hover {
                background-color: transparent;
            }

            .info-card:hover {
                transform: none;
                box-shadow: none;
            }

            .btn-action:hover {
                transform: none;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .payslip-header {
                padding: 25px;
            }

            .payslip-body {
                padding: 25px;
            }

            .payslip-title {
                font-size: 24px;
            }

            .info-cards-wrapper {
                grid-template-columns: 1fr;
            }

            .info-item {
                flex-direction: column;
                gap: 5px;
            }

            .info-value {
                text-align: left;
            }

            .net-salary-amount {
                font-size: 36px;
            }

            .action-buttons {
                flex-direction: column;
                padding: 20px;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
            }

            .payslip-table {
                font-size: 13px;
            }

            .payslip-table th,
            .payslip-table td {
                padding: 12px 10px;
            }
        }
    </style>
</head>

<body>
    <div class="payslip-container" id="payslipContainer">
        
        <!-- Header Section -->
        <div class="payslip-header">
            <div class="header-content">
                <h1 class="payslip-title">EMPLOYEE PAYSLIP</h1>
                <p class="payslip-subtitle">Payslip #MZ-<?= $payslip['id'] ?></p>
            </div>
        </div>

        <!-- Body Section -->
        <div class="payslip-body">
            
            <!-- Company & Employee Information Cards -->
            <div class="info-cards-wrapper">
                
                <!-- Company Information -->
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <h3 class="info-card-title">Company Information</h3>
                    </div>
                    
                    <?php if (getSetting('logo')): ?>
                        <img src="<?= getImage('settings', getSetting('logo')); ?>" alt="Company Logo" class="company-logo">
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <span class="info-label">Company Name:</span>
                        <span class="info-value"><?= getSetting('website_title') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Address:</span>
                        <span class="info-value"><?= getSetting('address') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?= getSetting('email') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?= getSetting('phone') ?></span>
                    </div>
                </div>

                <!-- Employee Information -->
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h3 class="info-card-title">Employee Information</h3>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?= $payslip['employee_name'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Position:</span>
                        <span class="info-value"><?= $payslip['designation_name'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Department:</span>
                        <span class="info-value"><?= $payslip['department_name'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?= $payslip['email'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?= $payslip['phone'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Created:</span>
                        <span class="info-value"><?= $payslip['created_at'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date Due:</span>
                        <span class="info-value"><?= $payslip['updated_at'] ?></span>
                    </div>
                </div>

            </div>

            <div class="divider"></div>

            <!-- Earnings Table -->
            <div class="payslip-table-wrapper">
                <table class="payslip-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-coins"></i> Earning</th>
                            <th>Details</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Basic Salary -->
                        <tr>
                            <td>Basic Salary</td>
                            <td>Monthly Basic Pay</td>
                            <td class="amount-earning">
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
                                    <td class="amount-earning">
                                        <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                                        <?= number_format($item['amount'], 2) ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <!-- Earning Total -->
                        <tr class="total-row">
                            <td colspan="2"><strong>Total Earnings</strong></td>
                            <td class="amount-earning">
                                <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                                <?= number_format($earningTotal, 2) ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Deductions Table -->
            <?php
            $deductionTotal = 0;
            $hasDeductions = false;
            foreach ($payslip['items'] as $item) {
                if ($item['item_type'] === 'deduction') {
                    $hasDeductions = true;
                    break;
                }
            }
            ?>

            <?php if ($hasDeductions): ?>
            <div class="payslip-table-wrapper">
                <table class="payslip-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-minus-circle"></i> Deduction</th>
                            <th>Details</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payslip['items'] as $item): ?>
                            <?php if ($item['item_type'] === 'deduction'): ?>
                                <?php $deductionTotal += $item['amount']; ?>
                                <tr>
                                    <td><?= $item['item_name'] ?></td>
                                    <td>-</td>
                                    <td class="amount-deduction">
                                        <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                                        <?= number_format($item['amount'], 2) ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <!-- Deduction Total -->
                        <tr class="total-row">
                            <td colspan="2"><strong>Total Deductions</strong></td>
                            <td class="amount-deduction">
                                <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                                <?= number_format($deductionTotal, 2) ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Net Salary Section -->
            <div class="net-salary-card">
                <div class="net-salary-label">NET SALARY (TAKE HOME)</div>
                <div class="net-salary-amount">
                    <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                    <?= number_format($payslip['net_salary'], 2) ?>
                </div>
            </div>

            <!-- Amount in Words -->
            <div class="amount-words-card">
                <div class="amount-words-label">
                    <i class="fas fa-file-invoice-dollar"></i> Amount in Words
                </div>
                <div class="amount-words-value">
                    <?= amountInWords($payslip['net_salary']) ?> Taka Only
                </div>
            </div>

            <!-- Payment Details -->
            <div class="payment-details-card">
                <?php if ($payslip['status'] === 'paid'): ?>
                    <h4 class="payment-details-title">
                        <i class="fas fa-credit-card"></i> Payment Details
                    </h4>

                    <div class="info-item">
                        <span class="info-label">Payment Method:</span>
                        <span class="info-value"><?= ucfirst($payslip['payment_method']) ?></span>
                    </div>

                    <?php if ($payslip['payment_method'] === 'bank'): ?>
                        <div class="info-item">
                            <span class="info-label">Bank Name:</span>
                            <span class="info-value"><?= htmlspecialchars($payslip['bank_name'] ?? '-') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Account Name:</span>
                            <span class="info-value"><?= htmlspecialchars($payslip['account_name'] ?? '-') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Account Number:</span>
                            <span class="info-value"><?= htmlspecialchars($payslip['account_number'] ?? '-') ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="info-item">
                        <span class="info-label">Status:</span>
                        <span class="info-value">
                            <span class="status-badge status-paid">
                                <i class="fas fa-check-circle"></i> PAID
                            </span>
                        </span>
                    </div>

                <?php else: ?>
                    <h4 class="payment-details-title">
                        <i class="fas fa-exclamation-circle"></i> Payment Status
                    </h4>
                    <div class="text-center">
                        <span class="status-badge status-pending">
                            <i class="fas fa-clock"></i> PENDING
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="payslip-footer">
                <p><i class="fas fa-info-circle"></i> This is a computer-generated payslip. No signature is required.</p>
                <p>For any queries, please contact the HR Department</p>
            </div>

        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button type="button" class="btn-action btn-print" onclick="printPayslip()">
                <i class="fas fa-print"></i> Print Payslip
            </button>
            <a href="<?= $GLOBALS['base_url'] ?>/download-payslip/<?= $payslip['id'] ?>" target="_blank" class="btn-action btn-download">
                <i class="fas fa-file-pdf"></i> Download PDF
            </a>
        </div>

    </div>

    <script>
        function printPayslip() {
            window.print();
        }
    </script>

</body>
</html>