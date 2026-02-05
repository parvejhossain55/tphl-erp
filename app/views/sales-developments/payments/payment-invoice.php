<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Booking & Payment Document</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Place favicon.ico in the root directory -->
    <link rel="shortcut icon" type="image/x-icon" href="<?= $GLOBALS['base_url'] ?>/assets/images/logo/tphl_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">



    <style>
        /* RESET */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box
        }

        /* BASE */
        body {
            font-family: Poppins, sans-serif;
            background: #ffffff;
            color: #2d3748;
            line-height: 1.5;
            padding: 20px;
        }

        /* CONTAINER */
        .document-container {
            max-width: 1200px;
            margin: auto;
            background: #ffffff;
            border: 1px solid #ddd;
        }

        /* HEADER */
        .document-header {
            background: #0f4c3a;
            color: #fff;
            padding: 16px;
            text-align: center;
        }

        .document-title {
            font-size: 22px;
            font-weight: 600;
        }

        .document-subtitle {
            font-size: 14px;
            opacity: .9;
        }

        /* BODY */
        .document-body {
            padding: 24px
        }

        /* SECTION */
        .section {
            margin-bottom: 24px
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            /* margin-bottom: 2rem; */
            padding-bottom: 1rem;
            border-bottom: 3px solid #d4af37;
            position: relative;
        }

        .section-header::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 100px;
            height: 3px;
            background: #0f4c3a;
        }

        .print-document-header {
            display: none;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f4c3a;
            letter-spacing: 0.5px;
        }

        .section-title2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f4c3a;
            letter-spacing: 0.5px;
        }



        /* GRID */
        .fields-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 12px;
        }

        /* FIELDS */
        .field-label {
            font-size: 14px;
            font-weight: 700;
            color: #555;
            margin-bottom: 4px;
            display: block;
        }

        .field-input {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #bbb;
            background: #f8f8f8;
            font-size: 14px;
        }

        .field-input[readonly] {
            background: #f1f1f1;
        }

        /* IMAGE */
        .site-logo img {
            max-height: 80px;
            width: auto;
        }

        .info-card {
            margin-top: 15px
        }

        /* .action-buttons{
    display:flex;
    gap:15px;
    margin-bottom:30px;
    justify-content:flex-end;
    width:100%;
} */


        /* UTIL */
        .full-width {
            grid-column: 1/-1
        }

        .no-print {
            display: block
        }


        /* =========================
   RESPONSIVE STYLES
========================= */

        /* Tablet: 570px - 768px */
        @media (max-width: 768px) and (min-width: 570px) {
            body {
                padding: 10px 0 !important;
            }

            /* 
            .document-container {
                padding: 16px;
            } */

            .site-logo img {
                max-height: 60px;
                width: auto;
                display: block;
                margin: auto;
            }

            .document-header {
                text-align: center;
                padding: 12px;
            }

            .document-title {
                font-size: 20px;
            }

            .company p {
                font-size: 13px;
                margin: 2px 0;
            }

            .fields-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 10px;
            }

            .field-label {
                font-size: 13px;
            }

            .field-input {
                font-size: 13px;
                padding: 6px 8px;
            }

            .section-title {
                font-size: 1.3rem;
            }

            .section-header::after {
                width: 80px;
            }

            img {
                max-width: 140px !important;
                max-height: 140px !important;
            }
        }

        /* Mobile: <570px */
        @media (max-width: 569px) {
            body {
                padding: 10px 0 !important;
            }

            /* .document-container {
                padding: 12px;
            } */

            /* Logo & company info stacked */
            .site-logo img {
                max-height: 50px;
                margin-bottom: 10px;
            }

            .document-header {
                text-align: center;
                padding: 10px;
            }

            .document-title {
                font-size: 18px;
            }

            .company p {
                font-size: 11px;
                margin: 1px 0;
            }

            /* Section Titles */
            .section-title {
                font-size: 1.2rem;
            }

            .section-header::after {
                width: 60px;
            }

            /* Fields Grid: 2 columns */
            .fields-grid {
                grid-template-columns: repeat(2, 1fr);
                /* দুই column per row */
                gap: 8px;
            }

            .field-label {
                font-size: 12px;
            }

            .field-input {
                font-size: 12px;
                padding: 5px 6px;
            }

            /* Images responsive inside container */
            img {
                max-width: 100% !important;
                height: auto !important;
                display: block;
                margin: 5px 0;
            }

            /* Client / Nominee Image alignment */
            .row.align-items-start {
                flex-direction: column !important;
            }

            .col-3.col-md-3.text-end {
                text-align: left !important;
                margin-top: 8px;
            }

            /* Section spacing reduce */
            .section {
                margin-bottom: 16px;
            }

            /* Print button / action spacing */
            .action-section {
                flex-direction: column;
                gap: 8px;
            }
        }



        /* PRINT */
        @media print {

            /* PAGE SETUP */
            @page {
                size: A4;
                margin: 10mm;
            }

            body {
                padding: 0;
                margin: 0;
                font-size: 10px;
                line-height: 1.25;
                background: #fff !important;
            }

            /* SHOW ONLY PRINT HEADER */
            .print-document-header {
                display: block !important;
                text-align: center;
                /* margin-bottom: 10px; */
            }

            .print-company p {
                margin: 0 !important;
                padding: 0 !important;
                line-height: 1.5;
            }

            /* HIDE NON PRINT */
            .no-print,
            .site-logo,
            .document-header {
                display: none !important;
            }

            /* CONTAINER */
            .document-container {
                border: none;
                max-width: 100%;
            }

            .document-body {
                padding: 10px;
            }

            /* SECTION SPACING REDUCE */
            .section {
                /* margin-bottom: 10px; */
                page-break-inside: avoid;
            }

            .section-header {
                padding-bottom: 4px;
                /* margin-bottom: 6px; */
                border-bottom: 1px solid #999;
            }

            .section-header::after {
                display: none;
            }

            .print-title {
                color: #0f4c3a;
                font-size: 12px;
                font-weight: 700;

            }

            .section-title,
            .section-title2 {
                font-size: 13px;
                font-weight: 700;
            }

            /* GRID → 4 COLUMN */
            .fields-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 6px;
            }

            /* LABEL + INPUT SMALL */
            .field-label {
                font-size: 10px;
                margin-bottom: 2px;
                font-weight: 700;
            }

            .field-input,
            textarea {
                font-size: 10px;
                padding: 4px 6px;
                min-height: auto;
                border: 1px solid #999;
                background: #fff !important;
            }

            .info-card {
                margin-top: 10px;
            }

            textarea {
                line-height: 1.2;
            }

            /* FULL WIDTH OVERRIDE */
            .full-width {
                grid-column: span 4;
            }

            /* IMAGES SMALL */
            img {
                max-width: 90px !important;
                max-height: 90px !important;
            }

            /* RULES + DECLARATION COMPACT */
            ul {
                font-size: 9.5px;
                margin-bottom: 6px;
            }

            li {
                margin-bottom: 3px;
            }

            /* SIGNATURE AREA */
            .signature,
            .signature div {
                font-size: 9px;
            }

            /* REMOVE EXTRA EFFECTS */
            * {
                box-shadow: none !important;
                animation: none !important;
                transition: none !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* WATERMARK */
            .document-container {
                position: relative;
            }

            .document-container::before {
                content: "";
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                width: 400px;
                height: 400px;
                background-image: url('<?= getImage("settings", getSetting("white_logo")) ?>');
                background-size: contain;
                background-repeat: no-repeat;
                background-position: center;
                opacity: 0.10;
                z-index: 1;
                pointer-events: none;
            }

            /* Ensure content stays above watermark */
            .document-body {
                position: relative;
                z-index: 2;
            }
        }
    </style>

</head>

<body>

    <div class="d-flex justify-content-end gap-3 mb-4 no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print Invoice
        </button>
        <button onclick="downloadPDF()" class="btn btn-success">
            <i class="fas fa-download"></i> Download PDF
        </button>
        <a href="<?= $GLOBALS['base_url'] ?>/payments-booking/<?= $invoice['id'] ?>"
            class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
    <div class="print-document">
        <div class="document-container" id="payment_booking">
            <!-- HEADER -->
            <div class="site-logo mt-3 mb-3">
                <img src="<?= getImage('settings', getSetting('white_logo')) ?>" alt="<?= getSetting('website_title', 'Site Logo') ?>">
            </div>


            <div class="print-document-header">
                <div class="print-header-content">
                    <h3 class="print-document-title">Property Booking Document</h3>
                    <div class="print-company ">
                        <p class="print-title"> <?= getSetting('website_title', '') ?></p>
                        <p>Phone: <?= getSetting('address', '') ?></p>
                        <p>Phone: <?= getSetting('phone', '') ?></p>
                        <p>Email: <?= getSetting('email', '') ?></p>
                    </div>
                </div>
            </div>
            <div class="document-header">
                <div class="header-content">
                    <h3 class="document-title">Property Booking Document</h3>
                    <div class="company ">
                        <p>Real Estate & Property Developer</p>
                        <p> <?= getSetting('website_title', '') ?></p>
                        <p>Phone: <?= getSetting('phone', '') ?></p>
                        <p>Email: <?= getSetting('email', '') ?></p>
                    </div>
                </div>
            </div>

            <!-- MAIN BODY -->
            <div class="document-body">

                <!-- CLIENT INFORMATION -->
                <div class="section">

                    <div class="row align-items-start mb-4">
                        <!-- LEFT : Section Title -->
                        <div class="col-9 col-md-9">
                            <div class="section-header">
                                <div class="section-icon"></div>
                                <h2 class="section-title">Client Information</h2>
                            </div>
                        </div>

                        <!-- RIGHT : Lead Image -->
                        <div class="col-3 col-md-3 text-end">
                            <div class="field-group">
                                <label class="field-label d-block">Client Image</label>
                                <img
                                    src="<?= getImage('leads', $invoice['lead_image']) ?>"
                                    alt="Lead Image"
                                    style="max-width:150px; border:1px solid #ddd; padding:10px; border-radius:6px;">
                            </div>
                        </div>
                    </div>


                    <!-- FORM FIELDS -->
                    <div class="fields-grid">

                        <!-- Full Name -->
                        <div class="field-group">
                            <label class="field-label">Full Name (Block Letters)</label>
                            <input type="text" class="field-input uppercase" value="<?= $invoice['full_name'] ?>">
                        </div>

                        <!-- Full Name Bangla -->
                        <div class="field-group">
                            <label class="field-label">পূর্ণ নাম (বাংলা)</label>
                            <input type="text" class="field-input" value="<?= $invoice['lead_name_bn'] ?>">
                        </div>

                        <!-- Father's Name -->
                        <div class="field-group">
                            <label class="field-label">Father's Name</label>
                            <input type="text" class="field-input" value="<?= $invoice['father_name'] ?>">
                        </div>

                        <!-- Father's Name Bangla -->
                        <div class="field-group">
                            <label class="field-label">পিতার নাম (বাংলা)</label>
                            <input type="text" class="field-input" value="<?= $invoice['father_name_bn'] ?>">
                        </div>

                        <!-- Mother's Name -->
                        <div class="field-group">
                            <label class="field-label">Mother's Name</label>
                            <input type="text" class="field-input" value="<?= $invoice['mother_name'] ?>">
                        </div>

                        <!-- Mother's Name Bangla -->
                        <div class="field-group">
                            <label class="field-label">মাতার নাম (বাংলা)</label>
                            <input type="text" class="field-input" value="<?= $invoice['mother_name_bn'] ?>">
                        </div>

                        <!-- Profession -->
                        <div class="field-group">
                            <label class="field-label">Profession</label>
                            <input type="text" class="field-input" value="<?= $invoice['profession'] ?>">
                        </div>

                        <!-- Profession Bangla -->
                        <div class="field-group">
                            <label class="field-label">পেশা (বাংলা)</label>
                            <input type="text" class="field-input" value="<?= $invoice['profession_bn'] ?>">
                        </div>

                        <!-- Date of Birth -->
                        <div class="field-group">
                            <label class="field-label">Date of Birth</label>
                            <input type="date" class="field-input" value="<?= $invoice['dob'] ?>">
                        </div>

                        <!-- NID / Passport -->
                        <div class="field-group">
                            <label class="field-label">NID / Passport / Birth Certificate</label>
                            <input type="text" class="field-input numeric" value="<?= $invoice['nid_or_passport'] ?>">
                        </div>

                        <!-- Identity Type -->
                        <div class="field-group">
                            <label class="field-label">Identity Type</label>
                            <input type="text" class="field-input" value="<?= $invoice['identity_type'] ?>">
                        </div>

                        <!-- Mobile -->
                        <div class="field-group">
                            <label class="field-label">Mobile Number</label>
                            <input type="tel" class="field-input numeric" value="<?= $invoice['phone'] ?>">
                        </div>

                        <!-- Email -->
                        <div class="field-group">
                            <label class="field-label">E-mail Address</label>
                            <input type="email" class="field-input" value="<?= $invoice['email'] ?>">
                        </div>
                        <!-- Nationality -->
                        <div class="field-group">
                            <label class="field-label">Nationality</label>
                            <input type="text" class="field-input" value="<?= $invoice['nationality'] ?>">
                        </div>

                        <!-- Religion -->
                        <div class="field-group">
                            <label class="field-label">Religion</label>
                            <input type="text" class="field-input" value="<?= $invoice['religion'] ?>">
                        </div>

                        <!-- Addresses -->
                        <div class="field-group">
                            <label class="field-label">Present Address</label>
                            <textarea class="field-input" readonly><?= $invoice['address'] ?></textarea>
                        </div>

                        <div class="field-group">
                            <label class="field-label">Permanent Address</label>
                            <textarea class="field-input" readonly><?= $invoice['permanent_address'] ?></textarea>
                        </div>



                    </div>
                </div>

                <div class="section">

                    <!-- Header + Image Row -->
                    <div class="row align-items-start mb-4">
                        <!-- LEFT : Section Title -->
                        <div class="col-9 col-md-9">
                            <div class="section-header">
                                <div class="section-icon"></div>
                                <h2 class="section-title">Nominee Information</h2>
                            </div>
                        </div>

                        <!-- RIGHT : Lead Image -->
                        <div class="col-3 col-md-3 text-end">
                            <div class="field-group">
                                <label class="field-label d-block">Nominee Image</label>
                                <img
                                    src="<?= getImage('leads', $invoice['nomi_image']) ?>"
                                    alt="Lead Image"
                                    style="max-width:150px; border:1px solid #ddd; padding:10px; border-radius:6px;">
                            </div>
                        </div>
                    </div>


                    <div class="fields-grid">
                        <div class="field-group ">
                            <label class="field-label">Full Name (Block Letters)</label>
                            <input type="text" class="field-input uppercase" value="<?= $invoice['nominee_name'] ?>">
                        </div>

                        <div class="field-group">
                            <label class="field-label">Father's Name</label>
                            <input type="text" class="field-input" value="<?= $invoice['nomi_father_name'] ?>">
                        </div>

                        <div class="field-group">
                            <label class="field-label">Mother's Name</label>
                            <input type="text" class="field-input" value="<?= $invoice['nomi_mother_name'] ?>">
                        </div>

                        <div class="field-group">
                            <label class="field-label">Profession</label>
                            <input type="text" class="field-input" value="<?= $invoice['nomi_profession'] ?>">
                        </div>

                        <div class="field-group d-none">
                            <label class="field-label">Relationship with Applicant</label>
                            <input type="text" class="field-input" value="<?= $invoice['relation_with_lead'] ?>">
                        </div>

                        <div class="field-group">
                            <label class="field-label">Date of Birth</label>
                            <input type="date" class="field-input" value="<?= $invoice['nomi_dob'] ?>">
                        </div>

                        <div class="field-group">
                            <label class="field-label">NID / Passport No / Birth Certificate</label>
                            <input type="text" class="field-input numeric" value="<?= $invoice['nomi_nid_passport'] ?>">
                        </div>

                        <div class="field-group">
                            <label class="field-label">Mobile Number</label>
                            <input type="tel" class="field-input numeric" value="<?= $invoice['nomi_phone'] ?>">
                        </div>

                        <div class="field-group">
                            <label class="field-label">E-mail Address</label>
                            <input type="email" class="field-input" value="<?= $invoice['nomi_email'] ?>">
                        </div>

                        <div class="field-group ">
                            <label class="field-label">Present Address</label>
                            <textarea class="field-input" readonly><?= $invoice['nomi_present_add'] ?></textarea>
                        </div>

                        <div class="field-group ">
                            <label class="field-label">Permanent Address</label>
                            <textarea class="field-input" readonly><?= $invoice['nomi_permanent_address'] ?></textarea>
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
                                value="<?= (($invoice['car_parking'] ?? 0) == 1) ? 'Yes' : 'No' ?>"
                                readonly>

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
                        <div class="info-card-title">Total Payable Amount</div>
                        <div class="info-card-value">
                            &#2547; <span id="totalAmount"><?= number_format($invoice['amount'], 2) ?></span>
                        </div>
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
                            <input type="date" class="field-input" value="<?= date('d M Y', strtotime($invoice['payment_date'])) ?>">
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


                        <div class="field-group full-width">
                            <label class="field-label">Amount in Words</label>
                            <input type="text"
                                class="field-input uppercase"
                                value="<?= amountInWords($invoice['amount'] ?? 0) ?>"
                                readonly>
                        </div>
                    </div>

                    <div class="section-header mt-3">
                        <h2 class="section-title">RULES OF FLAT PURCHASING WITH LAND</h2>
                    </div>

                    <?php $rulesArray = getFlatRulesArray(); ?>

                    <div style="margin-top: 3rem; padding: 2rem; background: linear-gradient(135deg, #e6f7f1 0%, #f0fdf4 100%); border-radius: 12px; border: 2px solid var(--primary-light);">
                        <div style="text-align: center;">
                            <ul style="list-style-type: decimal; padding-left: 1.5rem; text-align: left; color: var(--primary); font-weight: 600;">
                                <?php foreach ($rulesArray as $rule): ?>
                                    <li><?= htmlspecialchars($rule) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <p style=" font-size: 0.9rem; color: var(--text-light); margin-top: 1rem;" class="d-none">
                                Please verify all information before printing. This document serves as an official record of the transaction.
                            </p>
                        </div>
                    </div>
                    <!-- Footer Section -->
                    <div class="section-header my-2">
                        <h2 class="section-title2">DECLARATION</h2>
                    </div>
                    <div style=" display: flex; flex-direction: column; gap: 2rem; font-size: 0.95rem;">
                        <ul style="list-style-type: decimal; padding-left: 1.5rem; text-align: left; color: var(--primary); font-weight: 600;">

                            <oi><?= getSetting('client_declaration') ?? 'N/A' ?></oi>

                        </ul>

                        <!-- I Agree Checkbox -->
                        <div style="text-align: left;">
                            <label>
                                <input type="checkbox" checked disabled> I agree to the terms & conditions
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

            </div>

            <!-- Action Buttons -->
            <div class="action-section">

                <!-- <a href="<= $GLOBALS['base_url'] ?>/download-payslip/<= $payslip['id'] ?>" target="_blank" class="btn-action btn-download">
                <i class="fas fa-file-pdf"></i> Download PDF
            </a> -->
                <button target="_blank" class="btn-action btn-print d-none" onclick="downloadPaymentPdf()">
                    <i class="mdi mdi-download"></i> Download PDF
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.getElementById('payment_booking');
            const opt = {

                filename: 'Payment-Receipt-<?= str_pad($payment['id'] ?? '0', 6, '0', STR_PAD_LEFT) ?>-<?= date('Ymd') ?>.pdf',
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2,
                    logging: false
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait'
                }
            };

            html2pdf().set(opt).from(element).save();
        }
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const textareas = document.querySelectorAll("textarea.field-input");

            textareas.forEach(textarea => {
                textarea.style.height = "auto";
                textarea.style.height = textarea.scrollHeight + "px";
            });
        });


        document.addEventListener("DOMContentLoaded", function() {

            // make all inputs & textareas readonly
            document.querySelectorAll("input, textarea").forEach(el => {
                el.setAttribute("readonly", true);
            });

            // except checkbox (already disabled)
            document.querySelectorAll("input[type='checkbox']").forEach(el => {
                el.removeAttribute("readonly");
            });

        });
    </script>
</body>

</html>