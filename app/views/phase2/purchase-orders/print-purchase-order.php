<?php
/**
 * print-purchase-order.php
 * Standalone professional printable Purchase Order & CST Approval Sheet.
 */
$base_url = $GLOBALS['base_url'] ?? '';
$po = $po ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order PO-<?= htmlspecialchars($po['id'] ?? '') ?> — Print</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base_url ?>/public/assets/css/fontawesome.min.css">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: #f1f5f9;
            line-height: 1.45;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Screen Floating Action Bar ────────────────────────────── */
        .no-print-bar {
            background: #0f172a;
            color: #fff;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .no-print-bar .bar-title {
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .no-print-bar .bar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-act {
            padding: 7px 16px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            border: none;
            transition: opacity 0.2s;
        }
        .btn-print {
            background: #10b981;
            color: #fff;
        }
        .btn-back {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }
        .btn-act:hover {
            opacity: 0.9;
        }

        /* Document Screen Wrapper */
        .doc-page {
            max-width: 960px;
            margin: 24px auto 40px auto;
            background: #fff;
            padding: 32px 36px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.07);
            border-radius: 8px;
        }

        @media print {
            body {
                background: #fff !important;
                color: #000 !important;
            }
            .no-print-bar {
                display: none !important;
            }
            .doc-page {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
                border-radius: 0 !important;
            }
            @page {
                size: A4 portrait;
                margin: 8mm 10mm 8mm 10mm;
            }
        }
    </style>
</head>
<body>

<!-- Screen Only Action Bar -->
<div class="no-print-bar">
    <div class="bar-title">
        <i class="fal fa-file-contract"></i>
        <span>Purchase Order PO-<?= htmlspecialchars($po['id'] ?? '') ?> — Print Document</span>
    </div>
    <div class="bar-actions">
        <button onclick="window.print()" class="btn-act btn-print">
            <i class="fal fa-print"></i> Print Document
        </button>
        <a href="<?= $base_url ?>/con-purchase-order/<?= $po['id'] ?>" class="btn-act btn-back">
            <i class="fal fa-arrow-left"></i> Back to PO
        </a>
    </div>
</div>

<div class="doc-page">
    <?php require __DIR__ . '/print-po-sheet.php'; ?>
</div>

</body>
</html>
