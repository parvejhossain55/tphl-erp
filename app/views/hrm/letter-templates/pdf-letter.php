<!-- TCPDF Compatible Layout (background provided by controller as letterhead JPEG) -->
<style>
    .letter-body {
        font-family: helvetica;
        font-size: 11pt;
        color: #333;
        line-height: 1.6;
    }

    .subject {
        font-weight: bold;
        text-decoration: underline;
        margin-bottom: 20px;
        font-size: 12pt;
    }

    .signature {
        margin-top: 40px;
    }
</style>

<div class="letter-container">

    <!-- Letter Meta -->
    <table width="100%" style="margin-top: 20px;">
        <tr>
            <td align="left"><strong>Ref:</strong>
                <?= htmlspecialchars($letter['ref_number'] ?? 'TPHL/HR/' . date('Y') . '/00' . $letter['id']) ?></td>
            <td align="right"><strong>Date:</strong> <?= date('d F, Y', strtotime($letter['letter_date'])) ?></td>
        </tr>
    </table>

    <!-- Recipient -->
    <div class="recipient" style="margin-top: 20px;">
        <p style="margin: 0;"><strong>To,</strong></p>
        <span style="font-size: 13pt; font-weight: bold;"><?= htmlspecialchars($letter['full_name']) ?></span><br>
        <span style="font-size: 11pt;"><?= htmlspecialchars($letter['designation_name']) ?></span><br>
        <span style="font-size: 11pt;"><?= htmlspecialchars($letter['department_name']) ?></span><br>
        <span style="font-size: 10pt;"><strong>ID Code:</strong>
            <?= htmlspecialchars($letter['employee_code']) ?></span><br>
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