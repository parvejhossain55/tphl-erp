<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Letter - <?= htmlspecialchars($letter['subject']) ?></title>
    <style>
        /* A4 Layout Optimization */
        @page {
            size: A4;
            margin: 2cm;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Times New Roman', Times, serif; /* Formal font for print */
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
        }
        .letterhead {
            border-bottom: 2px solid #006400;
            padding-bottom: 20px;
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            width: 150px;
        }
        .company-info {
            text-align: right;
        }
        .company-info h2 {
            margin: 0;
            color: #006400;
            font-size: 18pt;
        }
        .company-info p {
            margin: 2px 0;
            font-size: 9pt;
            color: #444;
        }
        .letter-meta {
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
        }
        .subject {
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            margin-bottom: 30px;
        }
        .letter-body {
            text-align: justify;
            min-height: 500px;
        }
        .signature-section {
            margin-top: 50px;
        }
        .signatory-name {
            font-weight: bold;
            display: block;
            margin-top: 40px;
        }
        .signatory-title {
            font-style: italic;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8pt;
            color: #888;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="letterhead">
        <img src="<?= $GLOBALS['base_url'] ?>/assets/images/logo/tphl_logo.png" class="logo" alt="Logo">
        <div class="company-info">
            <h2>The Premium Homes Ltd.</h2>
            <p>123 Business Avenue, Dhaka, Bangladesh</p>
            <p>Phone: +880 1234 567890 | Website: www.tphl.com.bd</p>
        </div>
    </div>

    <div class="letter-meta">
        <div><strong>Ref:</strong> <?= htmlspecialchars($letter['ref_number'] ?? 'TPHL/HR/'.date('Y').'/00'.$letter['id']) ?></div>
        <div><strong>Date:</strong> <?= date('d F, Y', strtotime($letter['letter_date'])) ?></div>
    </div>

    <div class="subject">
        Subject: <?= htmlspecialchars($letter['subject']) ?>
    </div>

    <div class="letter-body">
        <?= $letter['body'] ?>
    </div>

    <div class="signature-section">
        <p>Sincerely,</p>
        <p>For <strong>The Premium Homes Ltd.</strong></p>
        
        <span class="signatory-name"><?= htmlspecialchars($letter['signatory_name']) ?></span>
        <span class="signatory-title"><?= htmlspecialchars($letter['signatory_title']) ?></span>
    </div>

    <div class="footer">
        This is an official document of The Premium Homes Ltd.
    </div>
</body>
</html>
