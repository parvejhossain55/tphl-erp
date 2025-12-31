<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Booking & Payment Document</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #0f4c3a;
            --primary-light: #1a6b4f;
            --accent-gold: #d4af37;
            --accent-rose: #e8b4b8;
            --dark: #1a1a1a;
            --text: #2d3748;
            --text-light: #718096;
            --border: #cbd5e0;
            --bg-cream: #fdfbf7;
            --bg-light: #f7fafc;
            --shadow: rgba(15, 76, 58, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            padding: 2rem 1rem;
            color: var(--text);
            line-height: 1.6;
        }

        .document-container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            border-radius: 16px;
            overflow: hidden;
            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* HEADER */
        .document-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            padding: 1rem;
            position: relative;
            overflow: hidden;
        }

        .document-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .document-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .header-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .document-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 600;
            color: white;
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .document-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 300;
            letter-spacing: 2px;
        }

        /* MAIN CONTENT */
        .document-body {
            padding: 3rem;
        }

        /* SECTION STYLING */
        .section {
            margin-bottom: 3rem;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid var(--accent-gold);
            position: relative;
        }

        .section-header::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 100px;
            height: 3px;
            background: var(--primary);
        }

        .section-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            box-shadow: 0 4px 12px var(--shadow);
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 0.5px;
        }

        /* PHOTO BOX */
        .photo-section {
            position: absolute;
            top: 2rem;
            right: 2rem;
            text-align: center;
        }

        .photo-box {
            width: 140px;
            height: 160px;
            border: 3px dashed var(--accent-gold);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: var(--bg-cream);
            color: var(--text-light);
            font-size: 0.9rem;
            padding: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .site-logo {
            text-align: center;
            /* margin-bottom: 20px; */
        }

        .site-logo img {
            max-height: 100px;
            width: 200px;
            object-fit: contain;
        }


        .photo-box:hover {
            border-color: var(--primary);
            background: white;
            transform: scale(1.05);
        }

        .photo-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        /* FIELDS GRID */
        .fields-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .field-group {
            position: relative;
        }

        .field-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            display: block;
        }

        .field-input {
            width: 100%;
            padding: 0.9rem 1.2rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            color: var(--text);
            background: var(--bg-light);
            transition: all 0.3s ease;
        }

        .field-input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(15, 76, 58, 0.1);
            transform: translateY(-2px);
        }

        .field-input::placeholder {
            color: var(--text-light);
            font-weight: 300;
        }

        textarea.field-input {
            min-height: 80px;
            resize: vertical;
            font-family: 'Poppins', sans-serif;
        }

        /* SPECIAL FIELD STYLES */
        .field-input.uppercase {
            text-transform: uppercase;
            font-weight: 500;
        }

        .field-input.numeric {
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.5px;
        }

        /* DECORATIVE ELEMENTS */
        .section-decorator {
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-gold) 0%, transparent 100%);
            margin: 1.5rem 0;
        }

        /* PRINT BUTTON */
        .print-button {
            position: fixed;
            top: 30px;
            right: 30px;
            z-index: 1000;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .btn-print {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(15, 76, 58, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-print:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(15, 76, 58, 0.4);
        }

        /* INFO CARDS */
        .info-card {
            background: linear-gradient(135deg, var(--bg-cream) 0%, white 100%);
            border-left: 4px solid var(--accent-gold);
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .info-card-title {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .info-card-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            font-variant-numeric: tabular-nums;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .document-body {
                padding: 2rem 1.5rem;
            }

            .document-title {
                font-size: 2rem;
            }

            .section-title {
                font-size: 1.5rem;
            }

            .fields-grid {
                grid-template-columns: 1fr;
            }

            .photo-section {
                position: static;
                margin: 2rem auto;
            }

            .print-button {
                position: fixed;
                bottom: 20px;
                right: 20px;
                top: auto;
            }
        }

        /* PRINT STYLES */
        @media print {

            .fields-grid {
                grid-template-columns: 1fr 1fr !important;
                gap: 12px;
            }

            .full-width {
                grid-column: 1 / -1 !important;
            }

            .section {
                margin-bottom: 20px;
                page-break-inside: avoid;
            }

            .document-header {
                background: #fff !important;
                color: #000 !important;
            }
        }


        /* ADDITIONAL STYLING */
        .full-width {
            grid-column: 1 / -1;
        }

        .highlight-field {
            background: linear-gradient(135deg, #fff9e6 0%, white 100%);
            border-color: var(--accent-gold);
        }

        .section-divider {
            height: 2px;
            background: linear-gradient(90deg, var(--primary) 0%, transparent 100%);
            margin: 2rem 0;
        }

        .company {
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="print-button">
        <button class="btn-print" onclick="window.print()">
            <span>🖨</span>
            <span>Print Document</span>
        </button>
    </div>

    <div class="document-container">
        <!-- HEADER -->
        <div class="site-logo mt-3 mb-3">
            <img src="<?= getImage('settings', getSetting('logo')) ?>" alt="<?= getSetting('website_title', 'Site Logo') ?>">
        </div>


        <div class="document-header">
            <div class="header-content">
                <h1 class="document-title">Property Booking Document</h1>
                <div class="company ">
                    <h2><?= getSetting('website_title', '') ?></h2>
                    <p>Real Estate & Property Developer</p>
                    <p>Phone: <?= getSetting('phone', '') ?></p>
                    <p>Email: <?= getSetting('email', '') ?></p>
                </div>
            </div>
        </div>

        <!-- MAIN BODY -->
        <div class="document-body">

            <!-- CLIENT INFORMATION -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon"></div>
                    <h2 class="section-title">Client Information</h2>
                </div>

                <div class="d-none photo-section">
                    <div class="photo-box">
                        <div class="photo-icon">📷</div>
                        <div>Passport<br>Size Photo</div>
                    </div>
                </div>

                <div class="fields-grid">
                    <div class="field-group full-width">
                        <label class="field-label">Full Name (Block Letters)</label>
                        <input type="text" class="field-input uppercase" value="<?= $invoice['lead_name'] ?>">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Father's Name</label>
                        <input type="text" class="field-input" value="<?= $invoice['father_name'] ?>">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Mother's Name</label>
                        <input type="text" class="field-input" value="<?= $invoice['mother_name'] ?>">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Profession</label>
                        <input type="text" class="field-input" value="<?= $invoice['profession'] ?>">
                    </div>

                    <div class="field-group d-none">
                        <label class="field-label">Relationship with Applicant</label>
                        <input type="text" class="field-input" placeholder="e.g., Self, Wife, Husband, Son">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Date of Birth</label>
                        <input type="date" class="field-input" value="<?= $invoice['dob'] ?>">
                    </div>

                    <div class="field-group">
                        <label class="field-label">NID / Passport No / Birth Certificate</label>
                        <input type="text" class="field-input numeric" value="<?= $invoice['nid_or_passport'] ?>">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Mobile Number</label>
                        <input type="tel" class="field-input numeric" value="<?= $invoice['phone'] ?>">
                    </div>

                    <div class="field-group">
                        <label class="field-label">E-mail Address</label>
                        <input type="email" class="field-input" value="<?= $invoice['email'] ?>">
                    </div>

                    <div class="field-group ">
                        <label class="field-label">Present Address</label>
                        <textarea class="field-input" readonly><?= $invoice['address'] ?></textarea>
                    </div>

                    <div class="field-group ">
                        <label class="field-label">Permanent Address</label>
                        <textarea class="field-input" readonly><?= $invoice['permanent_address'] ?></textarea>
                    </div>
                </div>
            </div>

            <div class="section-divider"></div>
            <!-- FLAT INFORMATION -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Flat Information</h2>
                </div>

                <div class="fields-grid">
                    <div class="d-none field-group">
                        <label class="field-label">File Number</label>
                        <input type="text" class="field-input highlight-field" value="<?= $invoice['flat_name'] ?>">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Booking Date</label>
                        <input type="date" class="field-input" value="<?= $invoice['dob'] ?>">
                    </div>


                    <div class="field-group ">
                        <label class="field-label">Project Name</label>
                        <input type="text" class="field-input" value="<?= $invoice['project_name'] ?>">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Flat Number</label>
                        <input type="text" class="field-input" value="<?= $invoice['flat_name'] ?>">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Flat Size (SQFT)</label>
                        <input type="text" class="field-input numeric" value="<?= $invoice['sqft'] ?>">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Car Parking</label>
                        <input type="text"
                            class="field-input"
                            value="<?= htmlspecialchars($invoice['electricity_backup'] ?? '') ?>"
                            <?= (($invoice['car_parking'] ?? '') === 'Yes') ? 'readonly' : '' ?>>

                    </div>

                    <div class="field-group">
                        <label class="field-label">Number of Shares</label>
                        <input type="number" class="field-input numeric" value="<?= $invoice['total_share'] ?>">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Land Share Price</label>
                        <input type="text" class="field-input numeric" value="<?= number_format($invoice['price_value'], 2) ?>">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Discount Amount</label>
                        <input type="text"
                            class="field-input numeric"
                            value="<?= number_format($invoice['discount_amount'] ?? 0, 2) ?>">

                    </div>
                </div>

                <div class="info-card">
                    <div class="info-card-title"> Total Payable Amount</div>
                    <div class="info-card-value">৳ <span id="totalAmount"><?= number_format($invoice['amount'], 2) ?></span></div>
                </div>
            </div>

            <div class="section-divider"></div>

            <!-- PAYMENT DETAILS -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Payment Details</h2>
                </div>

                <div class="fields-grid">
                    <div class="field-group">
                        <label class="field-label">Payment Method</label>
                        <input type="text" class="field-input" value="<?= ucfirst($invoice['payment_method']) ?>">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Payment Received Date</label>
                        <input type="date" class="field-input"
                            value="<?= date('Y-m-d', strtotime($invoice['payment_date'])) ?>">

                    </div>

                    <div class="field-group">
                        <label class="field-label">Payment Received Amount</label>
                        <input type="text" class="field-input numeric highlight-field" value="<?= number_format($invoice['amount'], 2) ?>">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Bank Name</label>
                        <input type="text" class="field-input" value="<?= htmlspecialchars($invoice['bank_name'] ?? '') ?>">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Cheque Number</label>
                        <input type="text" class="field-input numeric" value="<?= htmlspecialchars($invoice['cheque_no'] ?? '') ?>">
                    </div>

                    <div class="field-group d-none">
                        <label class="field-label">Cheque Date</label>
                        <input type="date" value="<?= number_format($invoice['amount'], 2) ?>">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Transaction ID</label>
                        <input type="text" class="field-input numeric" value="<?= htmlspecialchars($invoice['transaction_ref'] ?? '') ?>">
                    </div>

                    <div class="field-group d-none">
                        <label class="field-label">Note / Remarks</label>
                        <input type="text" class="field-input" value="<?= htmlspecialchars($invoice['note'] ?? '') ?>">
                    </div>
                    <?php
                    function amountInWords($amount)
                    {
                        if ($amount === null || $amount === '') {
                            return '';
                        }

                        $amount = (float)$amount;

                        $formatter = new NumberFormatter("en", NumberFormatter::SPELLOUT);
                        $words = $formatter->format($amount);

                        // Capitalize + Bangladeshi style
                        return strtoupper($words . " TAKA ONLY");
                    }
                    ?>

                    <div class="field-group full-width">
                        <label class="field-label">Amount in Words</label>
                        <input type="text"
                            class="field-input uppercase"
                            value="<?= amountInWords($invoice['amount'] ?? 0) ?>"
                            readonly>
                    </div>
                </div>

                <div class="section-decorator"></div>

                <div class="section-header">
    <h2 class="section-title">RULES OF FLAT PURCHASING WITH LAND</h2>
</div>

<?php $rulesArray = getFlatRulesArray(); ?>

<div style="margin-top: 3rem; padding: 2rem; background: linear-gradient(135deg, #e6f7f1 0%, #f0fdf4 100%); border-radius: 12px; border: 2px solid var(--primary-light);">
    <div style="text-align: center;">
        <ul style="list-style-type: decimal; padding-left: 1.5rem; text-align: left; color: var(--primary); font-weight: 600;">
            <?php foreach($rulesArray as $rule): ?>
                <li><?= htmlspecialchars($rule) ?></li>
            <?php endforeach; ?>
        </ul>
        <p style="font-size: 0.9rem; color: var(--text-light); margin-top: 1rem;">
            Please verify all information before printing. This document serves as an official record of the transaction.
        </p>
    </div>
</div>

<!-- Footer Section -->
<div style="margin-top: 3rem; display: flex; flex-direction: column; gap: 2rem; font-size: 0.95rem;">

    <!-- I Agree Checkbox -->
    <div style="text-align: left;">
        <label>
            <input type="checkbox" disabled checked> I agree to the terms & conditions
        </label>
    </div>

    <!-- Signature Rows -->
    <div style="display: flex; justify-content: space-between; margin-top: 2rem;">

        <!-- Left: Seller Name & Signature -->
        <div style="text-align: left;">
            <p>Seller Name & Signature</p>
            <div style="border-bottom: 1px solid #000; width: 200px; height: 1.5rem;"></div>
        </div>

        <!-- Right: Applicant & Authorized Signatures -->
        <div style="text-align: right;">
            <p>Applicant Signature</p>
            <div style="border-bottom: 1px solid #000; width: 200px; height: 1.5rem; margin-bottom: 1rem;"></div>

            <p>Authorized Signature</p>
            <div style="border-bottom: 1px solid #000; width: 200px; height: 1.5rem;"></div>
        </div>

    </div>
</div>

            </div>


            <div class="section-divider"></div>

            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Terms & Conditions</h2>
                </div>

                <div style="padding-left: 70px; margin-top: 1rem;">
                    <?= $flatRules ?>
                </div>
            </div>



        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>