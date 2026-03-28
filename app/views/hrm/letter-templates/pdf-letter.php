<!-- TCPDF Compatible Layout -->
<style>
    .letter-body {
        font-family: helvetica;
        font-size: 11pt;
        color: #333;
        line-height: 1.6;
    }
    .company-name {
        color: #006400;
        font-size: 18pt;
        font-weight: bold;
    }
    .company-info {
        font-size: 9pt;
        color: #555;
    }
    .ref-date {
        margin-top: 20px;
        margin-bottom: 30px;
    }
    .subject {
        font-weight: bold;
        text-decoration: underline;
        margin-bottom: 25px;
        font-size: 12pt;
    }
    .signature {
        margin-top: 40px;
    }
</style>

<div class="letter-container">
    <!-- Header Table -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td width="30%">
                <img src="<?= realpath('public/assets/images/logo/tphl_logo.png') ?>" width="140">
            </td>
            <td width="70%" align="right">
                <span class="company-name">The Premium Homes Ltd.</span><br>
                <span class="company-info">
                    Corporate Office: 123 Business Avenue, Dhaka, Bangladesh<br>
                    Phone: +880 1234 567890 | Email: hr@tphl.com.bd<br>
                    Web: www.tphl.com.bd
                </span>
            </td>
        </tr>
    </table>

    <hr style="color: #006400; height: 1px;">

    <!-- Letter Meta -->
    <table width="100%" style="margin-top: 20px;">
        <tr>
            <td align="left"><strong>Ref:</strong> <?= htmlspecialchars($letter['ref_number'] ?? 'TPHL/HR/'.date('Y').'/00'.$letter['id']) ?></td>
            <td align="right"><strong>Date:</strong> <?= date('d F, Y', strtotime($letter['letter_date'])) ?></td>
        </tr>
    </table>

    <!-- Recipient -->
    <div class="recipient" style="margin-top: 20px;">
        <p style="margin: 0;"><strong>To,</strong></p>
        <span style="font-size: 13pt; font-weight: bold;"><?= htmlspecialchars($letter['full_name']) ?></span><br>
        <span style="font-size: 11pt;"><?= htmlspecialchars($letter['designation_name']) ?></span><br>
        <span style="font-size: 11pt;"><?= htmlspecialchars($letter['department_name']) ?></span><br>
        <span style="font-size: 10pt;"><strong>ID Code:</strong> <?= htmlspecialchars($letter['employee_code']) ?></span><br>
        <div style="font-size: 11pt; margin-top: 10px;"><?= nl2br(htmlspecialchars($letter['address'] ?? '')) ?></div>
    </div>

    <br><br>

    <!-- Subject -->
    <div class="subject" align="center">
        Subject: <?= htmlspecialchars($letter['subject']) ?>
    </div>

    <!-- Letter Body -->
    <div class="letter-body">
        <?= $letter['body'] ?>
    </div>

    <!-- Signature -->
    <div class="signature">
        <p>Sincerely,</p>
        <p>For <strong>The Premium Homes Ltd.</strong></p>
        
        <br><br>
        <strong><?= htmlspecialchars($letter['signatory_name']) ?></strong><br>
        <i><?= htmlspecialchars($letter['signatory_title']) ?></i>
    </div>
</div>
