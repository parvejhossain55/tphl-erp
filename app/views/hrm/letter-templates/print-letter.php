<?php
$rawName = trim($letter['full_name'] ?? '');
if (preg_match('/^(Mr\.|Ms\.|Mrs\.|Dr\.|Engr\.)\s+/i', $rawName)) {
    $recipientName = htmlspecialchars($rawName);
} else {
    $recipientName = 'Mr./Ms. ' . htmlspecialchars($rawName);
}

$companyName = function_exists('getSetting') ? getSetting('website_title', 'The Premium Homes Limited') : 'The Premium Homes Limited';
if (stripos($companyName, 'Homes') === false && stripos($companyName, 'Home') !== false) {
    $companyName = str_ireplace('Home', 'Homes', $companyName);
}
if (stripos($companyName, 'Limited') === false && stripos($companyName, 'Ltd') === false) {
    $companyName .= ' Limited';
}

$isCaution = ($letter['letter_type'] === 'caution');
$isExperience = ($letter['letter_type'] === 'experience');

if ($isExperience) {
    $refNum = !empty($letter['ref_number']) ? $letter['ref_number'] : ('TPHL/HR/Experience Certificate/' . date('M-Y', strtotime($letter['letter_date'])));
} else {
    $refNum = !empty($letter['ref_number']) ? $letter['ref_number'] : ('TPHL/HR/' . date('Y', strtotime($letter['letter_date'])) . '/' . str_pad($letter['id'], 4, '0', STR_PAD_LEFT));
}

// Ensure non-salary-certificate letters never display salary certificate ref numbers
if ($letter['letter_type'] !== 'salary_certificate' && !empty($refNum) && stripos($refNum, 'Salary Certificate') !== false) {
    $refNum = 'TPHL/HR/' . date('Y', strtotime($letter['letter_date'])) . '/' . str_pad($letter['id'], 4, '0', STR_PAD_LEFT);
}

// Address sanitization: avoid literal 'N/A', 'NA', 'None', etc.
$cleanAddress = trim($letter['address'] ?? '');
$hasAddress = !empty($cleanAddress) && !in_array(strtoupper($cleanAddress), ['N/A', 'NA', 'NONE', 'NULL', '-']);

$printBody = $letter['body'] ?? '';
// Clean trailing empty paragraphs & excessive breaks to prevent accidental page spill
$printBody = preg_replace('/(<p[^>]*>(\s|&nbsp;|<br\s*\/?>)*<\/p>\s*)+$/i', '', trim($printBody));

// Detect manual page breaks
$hasExplicitPageBreak = (bool) preg_match('/(<div[^>]*class=["\'][^"\']*page-break[^"\']*["\'][^>]*>|<!--\s*pagebreak\s*-->|<hr[^>]*class=["\'][^"\']*page-break[^"\']*["\'][^>]*>)/i', $printBody);

$fitParam = isset($_GET['fit']) ? trim($_GET['fit']) : ($fitMode ?? '');
$initialDensity = 'compact';
if ($fitParam === '0' || $fitParam === 'normal') {
    $initialDensity = 'normal';
} elseif ($fitParam === 'ultra') {
    $initialDensity = 'ultra';
} elseif ($fitParam === '1' || $fitParam === 'compact') {
    $initialDensity = 'compact';
} elseif ($hasExplicitPageBreak) {
    $initialDensity = 'normal';
}


$baseDir = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4);
$sigUrl = '';
if (!empty($letter['custom_signature']) && file_exists($baseDir . '/' . ltrim($letter['custom_signature'], '/\\'))) {
    $sigUrl = $GLOBALS['base_url'] . '/' . ltrim($letter['custom_signature'], '/\\');
} elseif (file_exists($baseDir . '/public/assets/images/hrm/hr_signature.png')) {
    $sigUrl = $GLOBALS['base_url'] . '/public/assets/images/hrm/hr_signature.png';
} else {
    $dbConn = $GLOBALS['db'] ?? ($this->db ?? null);
    if ($dbConn) {
        $defRes = $dbConn->query("SELECT signature_path FROM hr_signatures WHERE status = 'active' ORDER BY is_default DESC, id DESC LIMIT 1");
        if ($defRes && $row = $defRes->fetch_assoc()) {
            if (file_exists($baseDir . '/' . ltrim($row['signature_path'], '/\\'))) {
                $sigUrl = $GLOBALS['base_url'] . '/' . ltrim($row['signature_path'], '/\\');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Official Letter - <?= htmlspecialchars($letter['subject']) ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 1.65;
            color: #111827;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ═══ Screen Presentation (Realistic Centered A4 Sheet) ═══ */
        @media screen {
            body {
                background-color: #525659;
                min-height: 100vh;
                padding: 24px 0 60px 0;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .sheet-container {
                position: relative;
                width: 210mm;
                height: 297mm;
                min-height: 297mm;
                max-height: 297mm;
                background-color: #ffffff;
                box-shadow: 0 8px 30px rgba(0, 0, 0, 0.35), 0 2px 8px rgba(0, 0, 0, 0.15);
                border-radius: 2px;
                margin: 0 auto 25px auto;
                overflow: hidden;
                box-sizing: border-box;
            }

            .bg-letterhead {
                position: absolute;
                top: 0;
                left: 0;
                width: 210mm;
                height: 297mm;
                pointer-events: none;
                z-index: 0;
            }

            .print-layout-table {
                position: relative;
                z-index: 1;
                width: 210mm;
                border-collapse: collapse;
                margin: 0 auto;
                border: none;
                background: transparent;
            }

            .page-divider {
                position: static !important;
                text-align: center;
                margin: 10px auto 25px auto;
                width: 210mm;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 14px;
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                user-select: none;
            }

            .page-divider::before,
            .page-divider::after {
                content: "";
                flex: 1;
                border-top: 1.5px dashed #94a3b8;
            }

            .page-badge {
                background: #334155;
                color: #f8fafc;
                font-size: 11px;
                font-weight: 700;
                padding: 4px 14px;
                border-radius: 9999px;
                letter-spacing: 0.6px;
                text-transform: uppercase;
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
            }
        }

        /* ═══ Printable Table Architecture: thead and tfoot reserve safe zones ═══ */
        .print-layout-table {
            width: 210mm;
            border-collapse: collapse;
            margin: 0 auto;
            border: none;
        }

        .print-header-space {
            height: 35mm;
            border: none;
            padding: 0;
            margin: 0;
        }

        .print-footer-space {
            height: 35mm;
            border: none;
            padding: 0;
            margin: 0;
        }

        .print-content-cell {
            border: none;
            padding: 0 20mm;
            vertical-align: top;
        }

        /* Letter content container with zoom support */
        .letter-content {
            width: 100%;
            display: block;
            position: relative;
            transform-origin: top center;
        }

        /* Page break support */
        .page-break {
            page-break-before: always;
            break-before: page;
            height: 0;
            line-height: 0;
            font-size: 0;
            margin: 0;
            padding: 0;
            border: none;
            display: block;
            clear: both;
        }

        /* Avoid breaking critical blocks across page boundaries */
        .letter-meta,
        .letter-recipient,
        .letter-subject,
        .caution-header-title,
        .caution-date,
        .caution-recipient-block,
        .caution-subject,
        .caution-salutation,
        .caution-signoff,
        .cert-meta-stacked,
        .cert-title-centered,
        .experience-signoff,
        .letter-extra-box,
        .table-extra-details,
        .letter-signature-section,
        .signature-block {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* ═══ Physical Print Presentation ═══ */
        @media print {
            html, body {
                background: transparent !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 210mm !important;
                display: block !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print {
                display: none !important;
            }

            .sheet-container {
                position: static !important;
                width: 210mm !important;
                min-height: 297mm !important;
                max-height: 297mm !important;
                margin: 0 !important;
                padding: 0 !important;
                background: transparent !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                overflow: hidden !important;
                page-break-after: always !important;
                break-after: page !important;
            }

            .sheet-container:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }

            .page-divider {
                display: none !important;
            }

            .bg-letterhead {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 210mm !important;
                height: 297mm !important;
                z-index: -1000 !important;
                pointer-events: none !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .print-layout-table {
                position: relative !important;
                width: 210mm !important;
                margin: 0 !important;
                border: none !important;
                background: transparent !important;
            }
        }

        /* ═══ Floating Print & Fit Toolbar ═══ */
        .print-toolbar {
            position: fixed;
            top: 16px;
            right: 24px;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 8px 14px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25), 0 2px 6px rgba(0, 0, 0, 0.1);
            user-select: none;
        }

        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 4px;
            background: #f1f5f9;
            padding: 3px 6px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .toolbar-label {
            font-size: 11.5px;
            font-weight: 700;
            color: #475569;
            margin-right: 4px;
            margin-left: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-density {
            padding: 5px 11px;
            background: transparent;
            color: #475569;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.15s ease;
        }

        .btn-density:hover {
            color: #0f172a;
            background: #e2e8f0;
        }

        .btn-density.active {
            background: #0f766e;
            color: #ffffff;
            box-shadow: 0 1px 4px rgba(15, 118, 110, 0.35);
        }

        .zoom-group {
            display: flex;
            align-items: center;
            gap: 4px;
            background: #f8fafc;
            padding: 3px 6px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .btn-zoom {
            width: 24px;
            height: 24px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #334155;
            transition: all 0.15s ease;
        }

        .btn-zoom:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #94a3b8;
        }

        .zoom-display {
            font-size: 11.5px;
            font-weight: 700;
            color: #0f766e;
            min-width: 40px;
            text-align: center;
            padding: 0 2px;
        }

        .btn-zoom-reset {
            padding: 3px 7px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            border-radius: 5px;
            cursor: pointer;
            font-size: 10.5px;
            font-weight: 600;
            color: #64748b;
            transition: all 0.15s ease;
        }

        .btn-zoom-reset:hover {
            background: #f1f5f9;
            color: #334155;
        }

        .btn-print {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            background: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            font-size: 12.5px;
            font-weight: 600;
            box-shadow: 0 1px 4px rgba(37, 99, 235, 0.35);
            transition: all 0.15s ease;
        }

        .btn-print:hover {
            background: #1d4ed8;
            box-shadow: 0 3px 8px rgba(37, 99, 235, 0.4);
        }

        .btn-close {
            padding: 6px 14px;
            background: #64748b;
            color: #ffffff;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            font-size: 12.5px;
            font-weight: 600;
            transition: all 0.15s ease;
        }

        .btn-close:hover {
            background: #475569;
        }

        /* ═══ Compact Mode (Fit to 1 Page) ═══ */
        body.compact-mode {
            font-size: 11.5px;
            line-height: 1.42;
        }
        body.compact-mode .print-header-space {
            height: 32mm;
        }
        body.compact-mode .print-footer-space {
            height: 30mm;
        }
        body.compact-mode .letter-meta {
            margin-bottom: 10px;
            font-size: 11.5px;
        }
        body.compact-mode .letter-recipient {
            margin-bottom: 10px;
            font-size: 11.5px;
            line-height: 1.35;
        }
        body.compact-mode .letter-recipient .recipient-name {
            font-size: 13px;
        }
        body.compact-mode .letter-subject {
            margin: 8px 0 10px;
        }
        body.compact-mode .letter-subject .subject-text {
            font-size: 12px;
        }
        body.compact-mode .letter-body {
            font-size: 11.5px;
            line-height: 1.42;
        }
        body.compact-mode .letter-body p {
            margin-bottom: 4px;
        }
        body.compact-mode .letter-body h1, 
        body.compact-mode .letter-body h2, 
        body.compact-mode .letter-body h3, 
        body.compact-mode .letter-body h4 {
            margin: 5px 0 2px 0;
            font-size: 12px;
        }
        body.compact-mode .letter-body ul, 
        body.compact-mode .letter-body ol {
            margin-bottom: 5px;
            padding-left: 18px;
        }
        body.compact-mode .letter-body li {
            margin-bottom: 2px;
        }
        body.compact-mode .letter-extra-box {
            margin: 8px 0 10px;
            padding: 6px 10px;
        }
        body.compact-mode .table-extra-details {
            font-size: 11.5px;
        }
        body.compact-mode .table-extra-details td {
            padding: 2px 4px;
        }
        body.compact-mode .letter-signature-section {
            margin-top: 10px;
            font-size: 11.5px;
        }
        body.compact-mode .signature-block {
            margin-top: 6px;
        }
        body.compact-mode .sig-img-container {
            height: 38px !important;
            margin-bottom: 3px !important;
        }
        body.compact-mode .sig-img-container img {
            height: 36px !important;
        }
        body.compact-mode .sign-line {
            width: 160px;
            margin-bottom: 4px;
        }
        body.compact-mode .signatory-name {
            font-size: 12px;
        }
        body.compact-mode .signatory-title {
            font-size: 11px;
        }
        body.compact-mode .signatory-dept {
            font-size: 10.5px;
        }

        /* Caution Letter Compact */
        body.compact-mode .caution-header-title {
            font-size: 14px;
            margin-bottom: 14px;
        }
        body.compact-mode .caution-date {
            margin-bottom: 4px;
            font-size: 11.5px;
        }
        body.compact-mode .caution-recipient-block {
            margin-bottom: 10px;
            line-height: 1.35;
            font-size: 11.5px;
        }
        body.compact-mode .caution-subject {
            margin: 8px 0 10px;
            font-size: 12px;
        }
        body.compact-mode .caution-salutation {
            margin-bottom: 8px;
            font-size: 12px;
        }
        body.compact-mode .caution-body {
            font-size: 11.5px;
            line-height: 1.45;
        }
        body.compact-mode .caution-signoff {
            margin-top: 18px;
            font-size: 12px;
        }

        /* Experience Letter Compact */
        body.compact-mode .cert-meta-stacked {
            margin-bottom: 14px;
            font-size: 11.5px;
            line-height: 1.4;
        }
        body.compact-mode .cert-title-centered {
            font-size: 14px;
            margin: 12px 0 14px;
        }
        body.compact-mode .experience-body {
            font-size: 11.5px;
            line-height: 1.48;
        }
        body.compact-mode .experience-signoff {
            margin-top: 16px;
            font-size: 11.5px;
        }
        body.compact-mode .experience-signoff .signoff-spacer {
            height: 30px;
        }

        /* ═══ Ultra Compact Mode (For Extra Long Letters) ═══ */
        body.ultra-compact {
            font-size: 10.5px;
            line-height: 1.32;
        }
        body.ultra-compact .print-header-space {
            height: 30mm;
        }
        body.ultra-compact .print-footer-space {
            height: 28mm;
        }
        body.ultra-compact .letter-meta {
            margin-bottom: 6px;
            font-size: 10.5px;
        }
        body.ultra-compact .letter-recipient {
            margin-bottom: 6px;
            font-size: 10.5px;
            line-height: 1.25;
        }
        body.ultra-compact .letter-recipient .recipient-name {
            font-size: 12px;
        }
        body.ultra-compact .letter-subject {
            margin: 6px 0 6px;
        }
        body.ultra-compact .letter-subject .subject-text {
            font-size: 11px;
        }
        body.ultra-compact .letter-body {
            font-size: 10.5px;
            line-height: 1.32;
        }
        body.ultra-compact .letter-body p {
            margin-bottom: 3px;
        }
        body.ultra-compact .letter-body h1, 
        body.ultra-compact .letter-body h2, 
        body.ultra-compact .letter-body h3, 
        body.ultra-compact .letter-body h4 {
            margin: 4px 0 2px 0;
            font-size: 11px;
        }
        body.ultra-compact .letter-extra-box {
            margin: 6px 0 8px;
            padding: 4px 8px;
        }
        body.ultra-compact .table-extra-details {
            font-size: 10.5px;
        }
        body.ultra-compact .table-extra-details td {
            padding: 1px 3px;
        }
        body.ultra-compact .letter-signature-section {
            margin-top: 8px;
            font-size: 10.5px;
        }
        body.ultra-compact .signature-block {
            margin-top: 4px;
        }
        body.ultra-compact .sig-img-container {
            height: 32px !important;
            margin-bottom: 2px !important;
        }
        body.ultra-compact .sig-img-container img {
            height: 30px !important;
        }
        body.ultra-compact .sign-line {
            width: 140px;
            margin-bottom: 3px;
        }
        body.ultra-compact .signatory-name {
            font-size: 11px;
        }
        body.ultra-compact .signatory-title {
            font-size: 10px;
        }
        body.ultra-compact .signatory-dept {
            font-size: 9.5px;
        }

        /* ═══ Standard Letter Styling ═══ */
        .letter-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #111827;
            margin-bottom: 18px;
        }

        .letter-recipient {
            margin-bottom: 18px;
            font-size: 13px;
            line-height: 1.5;
            color: #111827;
        }

        .letter-recipient .recipient-to {
            font-weight: 700;
            margin-bottom: 3px;
            color: #000;
        }

        .letter-recipient .recipient-name {
            font-size: 14.5px;
            font-weight: 700;
            color: #000;
            margin-bottom: 2px;
        }

        .letter-recipient .recipient-detail {
            color: #111827;
        }

        .letter-recipient .recipient-id {
            margin-top: 2px;
            font-size: 13px;
        }

        .letter-recipient .recipient-address {
            margin-top: 4px;
            font-size: 13px;
            color: #4b5563;
        }

        .letter-subject {
            margin: 16px 0;
            text-align: left;
        }

        .letter-subject .subject-text {
            font-size: 13.5px;
            font-weight: 700;
            text-decoration: underline;
            color: #000;
        }

        .letter-body {
            font-size: 13px;
            line-height: 1.55;
            color: #111827;
            text-align: justify;
            text-justify: inter-word;
            flex-grow: 1;
        }

        .letter-body p {
            margin-bottom: 7px;
        }

        .letter-body h1, .letter-body h2, .letter-body h3, .letter-body h4 {
            margin: 8px 0 3px 0;
            font-size: 13.5px;
            color: #0f172a;
        }

        .letter-body ul, .letter-body ol {
            margin-bottom: 8px;
            padding-left: 20px;
        }

        .letter-body li {
            margin-bottom: 3px;
        }

        /* ═══ Caution Letter Specific Styling ═══ */
        .caution-header-title {
            font-size: 16px;
            font-weight: 800;
            text-align: center;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #000;
            margin-bottom: 22px;
        }

        .caution-date {
            font-size: 13px;
            font-weight: 600;
            color: #000;
            margin-bottom: 6px;
        }

        .caution-recipient-block {
            font-size: 13px;
            line-height: 1.45;
            color: #000;
            margin-bottom: 16px;
        }

        .caution-recipient-block .caution-to {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .caution-recipient-block .caution-emp-name {
            font-weight: 700;
            font-size: 14px;
        }

        .caution-recipient-block .caution-line {
            color: #111;
        }

        .caution-subject {
            font-size: 13.5px;
            margin: 14px 0 16px 0;
            color: #000;
        }

        .caution-salutation {
            font-size: 13.5px;
            font-weight: 700;
            color: #000;
            margin-bottom: 12px;
        }

        .caution-body {
            font-size: 13px;
            line-height: 1.72;
            color: #000;
        }

        .caution-signoff {
            margin-top: 36px;
            font-size: 13.5px;
            font-weight: 700;
            color: #000;
        }

        /* ═══ Experience Certificate Specific Styling ═══ */
        .cert-meta-stacked {
            font-size: 13px;
            line-height: 1.6;
            color: #000;
            margin-bottom: 24px;
        }

        .cert-meta-stacked .meta-line {
            font-weight: 600;
        }

        .cert-title-centered {
            font-size: 15.5px;
            font-weight: 800;
            text-align: center;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #000;
            margin: 20px 0 24px 0;
        }

        .experience-body {
            font-size: 13px;
            line-height: 1.8;
            color: #000;
        }

        .experience-signoff {
            margin-top: 30px;
            font-size: 13px;
            line-height: 1.45;
            color: #000;
        }

        .experience-signoff .signoff-closing {
            font-weight: 600;
            margin-bottom: 0;
        }

        .experience-signoff .signoff-spacer {
            height: 45px;
        }

        .experience-signoff .signoff-dept {
            font-size: 13px;
            color: #000;
        }

        .experience-signoff .signoff-company {
            font-size: 13px;
            color: #000;
        }

        /* Extra Details */
        .letter-extra-box {
            margin: 14px 0 18px;
            padding: 10px 14px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }

        .table-extra-details {
            width: 100%;
            font-size: 13px;
            border-collapse: collapse;
        }

        .table-extra-details td {
            padding: 4px 6px;
            vertical-align: middle;
        }

        .table-extra-details .extra-label {
            width: 220px;
            color: #475569;
            font-weight: 600;
        }

        .table-extra-details .extra-sep {
            width: 15px;
            color: #64748b;
            font-weight: 700;
        }

        .table-extra-details .extra-val {
            color: #0f172a;
        }

        /* Signatory Section */
        .letter-signature-section {
            margin-top: 14px;
            font-size: 13px;
            color: #1e293b;
        }

        .company-sign-for {
            margin-bottom: 0;
            color: #334155;
        }

        .signature-block {
            margin-top: 12px;
            display: inline-block;
        }

        .sign-line {
            border-top: 1.5px solid #334155;
            width: 180px;
            margin-bottom: 6px;
        }

        .signatory-name {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
            display: block;
        }

        .signatory-title {
            font-size: 12.5px;
            color: #475569;
            font-style: italic;
            display: block;
            margin-top: 1px;
        }

        .signatory-dept {
            font-size: 12px;
            color: #64748b;
            display: block;
            margin-top: 1px;
        }
    </style>
</head>

<body class="<?= $initialDensity === 'compact' ? 'compact-mode' : ($initialDensity === 'ultra' ? 'ultra-compact' : '') ?>">
    <!-- Floating print & fit toolbar -->
    <div class="print-toolbar no-print">
        <div class="toolbar-group">
            <span class="toolbar-label">Page Fit:</span>
            <button type="button" class="btn-density <?= $initialDensity === 'normal' ? 'active' : '' ?>" data-density="normal" onclick="setDensity('normal')" title="Standard normal spacing">Normal</button>
            <button type="button" class="btn-density <?= $initialDensity === 'compact' ? 'active' : '' ?>" data-density="compact" onclick="setDensity('compact')" title="Auto-scale & compact spacing to fit on 1 single page">Fit to 1 Page</button>
            <button type="button" class="btn-density <?= $initialDensity === 'ultra' ? 'active' : '' ?>" data-density="ultra" onclick="setDensity('ultra')" title="Ultra compact spacing for long multi-clause letters">Ultra</button>
        </div>

        <div class="zoom-group">
            <span class="toolbar-label">Zoom:</span>
            <button type="button" class="btn-zoom" onclick="adjustZoom(-0.03)" title="Zoom Out (shrink to fit)">−</button>
            <span id="zoomDisplay" class="zoom-display">100%</span>
            <button type="button" class="btn-zoom" onclick="adjustZoom(0.03)" title="Zoom In (enlarge)">+</button>
            <button type="button" class="btn-zoom-reset" onclick="resetZoom()" title="Reset to auto-fit scale">↺ Auto</button>
        </div>

        <button type="button" class="btn-print" onclick="triggerPrint()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Print
        </button>
        <button type="button" class="btn-close" onclick="window.close()">Close</button>
    </div>

    <!-- Centered A4 Paper Sheet Container -->
    <div class="sheet-container" id="sheetContainer">
        <!-- Letterhead background image -->
        <img src="<?= $GLOBALS['base_url'] ?>/assets/email-template/new-letter-template.jpeg" class="bg-letterhead"
            alt="Letterhead">

        <table class="print-layout-table">
            <thead>
                <tr><th class="print-header-space">&nbsp;</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td class="print-content-cell">
                        <div class="letter-content" id="letterContent">

            <?php if ($isCaution): ?>
                <!-- ═══════════════════ CAUTION LETTER FORMAT ═══════════════════ -->
                <div class="caution-header-title">CAUTION LETTER</div>

                <div class="caution-date">Date: <?= date('F d, Y', strtotime($letter['letter_date'])) ?></div>

                <div class="caution-recipient-block">
                    <div class="caution-to">To,</div>
                    <div class="caution-emp-name"><?= htmlspecialchars($rawName) ?></div>
                    <div class="caution-line">Emp ID: <?= htmlspecialchars($letter['employee_code']) ?></div>
                    <div class="caution-line">Designation: <?= htmlspecialchars($letter['designation_name'] ?? 'N/A') ?></div>
                    <div class="caution-line">Department: <?= htmlspecialchars($letter['department_name'] ?? 'N/A') ?></div>
                </div>

                <div class="caution-subject">
                    <strong>Subject:</strong> <?= htmlspecialchars($letter['subject']) ?>
                </div>

                <?php if (stripos(trim(strip_tags($letter['body'])), 'Dear ') !== 0): ?>
                    <div class="caution-salutation">
                        Dear <?= htmlspecialchars($rawName) ?>,
                    </div>
                <?php endif; ?>

                <div class="letter-body caution-body">
                    <?= $printBody ?>
                </div>

                <?php if (stripos($letter['body'], 'Head of HR') === false): ?>
                    <div class="caution-signoff">
                        Head of HR | <?= htmlspecialchars($companyName) ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($isExperience): ?>
                <!-- ═══════════════════ EXPERIENCE CERTIFICATE FORMAT ═══════════════════ -->
                <div class="cert-meta-stacked">
                    <div class="meta-line"><strong>Ref:</strong> <?= htmlspecialchars($refNum) ?></div>
                    <div class="meta-line"><strong>Date:</strong> <?= date('F d, Y', strtotime($letter['letter_date'])) ?></div>
                </div>

                <div class="cert-title-centered">TO WHOM IT MAY CONCERN</div>

                <div class="letter-body experience-body">
                    <?= $printBody ?>
                </div>

                <?php if (stripos($letter['body'], 'Human Resources Department') === false): ?>
                    <div class="experience-signoff">
                        <div class="signoff-closing">Sincerely,</div>
                        <div class="signoff-company" style="margin-bottom: 5px;">For <strong><?= htmlspecialchars($companyName) ?></strong></div>
                        <?php if ((!isset($letter['include_signature']) || $letter['include_signature'] == 1) && !empty($sigUrl)): ?>
                            <div class="sig-img-container" style="height: 48px; margin-bottom: 5px;">
                                <img src="<?= $sigUrl ?>" alt="Signature" style="height: 45px; max-width: 170px; object-fit: contain;">
                            </div>
                        <?php else: ?>
                            <div class="signoff-spacer sig-img-container"></div>
                        <?php endif; ?>
                        <?php if (!empty($letter['signatory_name'])): ?>
                            <div style="font-weight: 700;"><?= htmlspecialchars($letter['signatory_name']) ?></div>
                            <?php if (!empty($letter['signatory_title'])): ?>
                                <div style="color: #475569;"><?= htmlspecialchars($letter['signatory_title']) ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <div class="signoff-dept">Human Resources Department</div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- ═══════════════════ STANDARD / OTHER LETTER FORMAT ═══════════════════ -->
                <!-- Reference Number & Date -->
                <div class="letter-meta">
                    <div class="ref-no">
                        <strong>Ref:</strong> <?= htmlspecialchars($refNum) ?>
                    </div>
                    <div class="date-issued">
                        <strong>Date:</strong> <?= date('d F, Y', strtotime($letter['letter_date'])) ?>
                    </div>
                </div>

                <!-- Recipient Address Block -->
                <div class="letter-recipient">
                    <div class="recipient-to">To,</div>
                    <div class="recipient-name"><?= $recipientName ?></div>
                    <div class="recipient-detail"><?= htmlspecialchars($letter['designation_name'] ?? 'N/A') ?></div>
                    <div class="recipient-detail"><?= htmlspecialchars($letter['department_name'] ?? 'N/A') ?></div>
                    <div class="recipient-id"><strong>Employee ID:</strong> <?= htmlspecialchars($letter['employee_code']) ?></div>
                    <?php if ($hasAddress): ?>
                        <div class="recipient-address"><?= nl2br(htmlspecialchars($cleanAddress)) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Subject Line -->
                <div class="letter-subject">
                    <span class="subject-text">
                        <strong>Subject:</strong> <?= htmlspecialchars($letter['subject']) ?>
                    </span>
                </div>

                <!-- Main Letter Body -->
                <div class="letter-body">
                    <?= $printBody ?>
                </div>

                <!-- Extra Details (Promotion, Increment, Designation changes) -->
                <?php
                $hasExtra = !empty($letter['effective_date']) || !empty($letter['new_designation']) || (!empty($letter['current_salary']) && (float)$letter['current_salary'] > 0) || (!empty($letter['revised_salary']) && (float)$letter['revised_salary'] > 0);
                ?>
                <?php if ($hasExtra): ?>
                    <div class="letter-extra-box">
                        <table class="table-extra-details">
                            <?php if (!empty($letter['effective_date'])): ?>
                                <tr>
                                    <td class="extra-label">Effective Date</td>
                                    <td class="extra-sep">:</td>
                                    <td class="extra-val"><?= date('d F, Y', strtotime($letter['effective_date'])) ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($letter['new_designation'])): ?>
                                <tr>
                                    <td class="extra-label">New Position / Designation</td>
                                    <td class="extra-sep">:</td>
                                    <td class="extra-val"><strong><?= htmlspecialchars($letter['new_designation']) ?></strong></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($letter['current_salary']) && (float)$letter['current_salary'] > 0): ?>
                                <tr>
                                    <td class="extra-label">Current Gross Salary</td>
                                    <td class="extra-sep">:</td>
                                    <td class="extra-val">BDT <?= number_format((float)$letter['current_salary'], 2) ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($letter['revised_salary']) && (float)$letter['revised_salary'] > 0): ?>
                                <tr>
                                    <td class="extra-label">Revised Gross Salary</td>
                                    <td class="extra-sep">:</td>
                                    <td class="extra-val"><strong>BDT <?= number_format((float)$letter['revised_salary'], 2) ?></strong></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- Official Signatory Block -->
                <div class="letter-signature-section">
                    <p class="mb-1">Sincerely,</p>
                    <p class="company-sign-for">For <strong><?= htmlspecialchars($companyName) ?></strong></p>
                    <div class="signature-block">
                        <?php if ((!isset($letter['include_signature']) || $letter['include_signature'] == 1) && !empty($sigUrl)): ?>
                            <div class="sig-img-container" style="height: 48px; margin-bottom: 5px;">
                                <img src="<?= $sigUrl ?>" alt="Signature" style="height: 45px; max-width: 170px; object-fit: contain;">
                            </div>
                        <?php else: ?>
                            <div class="sig-img-container" style="height: 48px; margin-bottom: 5px;"></div>
                        <?php endif; ?>
                        <div class="sign-line"></div>
                        <span class="signatory-name"><?= htmlspecialchars($letter['signatory_name']) ?></span>
                        <span class="signatory-title"><?= htmlspecialchars($letter['signatory_title']) ?></span>
                        <span class="signatory-dept">Human Resources Department</span>
                    </div>
                </div>
            <?php endif; ?>

                    </div>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td class="print-footer-space">&nbsp;</td>
            </tr>
        </tfoot>
    </table>
    </div> <!-- /.sheet-container -->

    <script>
        let currentDensity = '<?= $initialDensity ?>';
        let currentZoom = 1.0;
        let originalContentHTML = null;

        function updateZoomDisplay(val) {
            const el = document.getElementById('zoomDisplay');
            if (el) {
                el.textContent = Math.round(val * 100) + '%';
            }
        }

        function restoreOriginalContent() {
            const content = document.getElementById('letterContent');
            if (!content) return;

            // Remove any dynamically created sheet2 and pageDivider
            document.querySelectorAll('#sheet2').forEach(el => el.remove());
            document.querySelectorAll('#pageDivider, .page-divider').forEach(el => el.remove());

            if (originalContentHTML !== null) {
                content.innerHTML = originalContentHTML;
            } else {
                originalContentHTML = content.innerHTML;
            }
        }

        function paginateMultiPage() {
            const sheet1 = document.getElementById('sheetContainer');
            const letterContent = document.getElementById('letterContent');
            if (!sheet1 || !letterContent) return;

            // Safe available height for page 1 content (297mm - 35mm header - 35mm footer - safety buffer)
            const maxH = 845;
            const actualHeight = letterContent.scrollHeight;

            // If it naturally fits on 1 page without zooming, do not paginate
            if (actualHeight <= maxH) {
                return;
            }

            const contentTop = letterContent.getBoundingClientRect().top;
            const bodyEl = letterContent.querySelector('.letter-body, .caution-body, .experience-body');
            const extraBox = letterContent.querySelector('.letter-extra-box');
            const sigSection = letterContent.querySelector('.letter-signature-section, .caution-signoff, .experience-signoff');

            let splitNode = null;
            let splitInsideBody = false;

            // Check if there is an explicit page-break element
            const explicitBreak = letterContent.querySelector('.page-break');
            if (explicitBreak) {
                splitNode = explicitBreak;
                if (bodyEl && bodyEl.contains(explicitBreak)) {
                    splitInsideBody = true;
                }
            } else if (bodyEl) {
                const bodyChildren = Array.from(bodyEl.children);
                for (let i = 0; i < bodyChildren.length; i++) {
                    const child = bodyChildren[i];
                    const bottom = child.getBoundingClientRect().bottom - contentTop;
                    if (bottom > maxH) {
                        // If previous child was a heading (e.g. <h3>), split at heading to avoid leaving lone heading on page 1
                        if (i > 0 && /^H[1-6]$/i.test(bodyChildren[i - 1].tagName)) {
                            splitNode = bodyChildren[i - 1];
                        } else {
                            splitNode = child;
                        }
                        splitInsideBody = true;
                        break;
                    }
                }
                // If all body elements fit, but extraBox or sigSection exceeds maxH
                if (!splitNode) {
                    if (extraBox && (extraBox.getBoundingClientRect().bottom - contentTop > maxH)) {
                        splitNode = extraBox;
                    } else if (sigSection && (sigSection.getBoundingClientRect().bottom - contentTop > maxH)) {
                        splitNode = sigSection;
                    }
                }
            } else {
                const children = Array.from(letterContent.children);
                for (let i = 0; i < children.length; i++) {
                    const child = children[i];
                    if (child.classList.contains('letter-meta') || child.classList.contains('letter-recipient') || child.classList.contains('letter-subject') || child.classList.contains('caution-header-title')) {
                        continue;
                    }
                    const bottom = child.getBoundingClientRect().bottom - contentTop;
                    if (bottom > maxH) {
                        splitNode = child;
                        break;
                    }
                }
            }

            if (!splitNode) return;

            // Create Sheet 2
            const sheet2 = document.createElement('div');
            sheet2.className = sheet1.className;
            sheet2.id = 'sheet2';

            const bgImgSrc = sheet1.querySelector('.bg-letterhead').getAttribute('src');
            sheet2.innerHTML = `
                <img src="${bgImgSrc}" class="bg-letterhead" alt="Letterhead">
                <table class="print-layout-table">
                    <thead><tr><th class="print-header-space">&nbsp;</th></tr></thead>
                    <tbody>
                        <tr>
                            <td class="print-content-cell">
                                <div class="letter-content" id="letterContentPage2"></div>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot><tr><td class="print-footer-space">&nbsp;</td></tr></tfoot>
                </table>
            `;

            const content2 = sheet2.querySelector('#letterContentPage2');

            if (splitInsideBody && bodyEl) {
                const body2 = document.createElement('div');
                body2.className = bodyEl.className;

                let moving = false;
                const bodyChildren = Array.from(bodyEl.childNodes);
                bodyChildren.forEach(child => {
                    if (child === splitNode) moving = true;
                    if (moving) {
                        body2.appendChild(child);
                    }
                });
                content2.appendChild(body2);

                // Also move any subsequent siblings of bodyEl (extraBox, sigSection)
                let movingSiblings = false;
                const letterChildren = Array.from(letterContent.children);
                letterChildren.forEach(child => {
                    if (child === bodyEl) {
                        movingSiblings = true;
                        return;
                    }
                    if (movingSiblings) {
                        content2.appendChild(child);
                    }
                });
            } else {
                let moving = false;
                const letterChildren = Array.from(letterContent.children);
                letterChildren.forEach(child => {
                    if (child === splitNode) moving = true;
                    if (moving) {
                        content2.appendChild(child);
                    }
                });
            }

            // Clean up any existing divider or sheet2 before inserting
            document.querySelectorAll('#sheet2').forEach(el => el.remove());
            document.querySelectorAll('#pageDivider, .page-divider').forEach(el => el.remove());

            // Insert Divider & Sheet 2 into the page
            const divider = document.createElement('div');
            divider.id = 'pageDivider';
            divider.className = 'page-divider no-print';
            divider.innerHTML = '<span class="page-badge">Page 2 of 2</span>';

            sheet1.parentNode.insertBefore(divider, sheet1.nextSibling);
            sheet1.parentNode.insertBefore(sheet2, divider.nextSibling);
        }

        function applyFitAndPagination() {
            restoreOriginalContent();

            const content = document.getElementById('letterContent');
            if (!content) return;

            if (currentDensity === 'normal') {
                currentZoom = 1.0;
                content.style.zoom = '1';
                updateZoomDisplay(1.0);
                paginateMultiPage();
                return;
            }

            // Compact or Ultra mode: scale to fit strictly on 1 page!
            content.style.zoom = '1';
            const maxSafeHeight = (currentDensity === 'ultra') ? 885 : 864;
            const actualHeight = content.scrollHeight;

            if (actualHeight > maxSafeHeight) {
                let ratio = maxSafeHeight / actualHeight;
                ratio = Math.max(0.55, Math.min(0.99, ratio));
                currentZoom = ratio;
                content.style.zoom = ratio.toFixed(3);
                updateZoomDisplay(ratio);
            } else {
                currentZoom = 1.0;
                content.style.zoom = '1';
                updateZoomDisplay(1.0);
            }
        }

        function setDensity(mode) {
            currentDensity = mode;
            document.body.classList.remove('compact-mode', 'ultra-compact');
            if (mode === 'compact') {
                document.body.classList.add('compact-mode');
            } else if (mode === 'ultra') {
                document.body.classList.add('ultra-compact');
            }

            document.querySelectorAll('.btn-density').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-density') === mode);
            });

            try {
                localStorage.setItem('letter_print_density', mode);
            } catch (e) {}

            applyFitAndPagination();
        }

        function adjustZoom(delta) {
            const content = document.getElementById('letterContent');
            if (!content) return;

            currentZoom = Math.max(0.40, Math.min(1.40, currentZoom + delta));
            content.style.zoom = currentZoom.toFixed(3);
            updateZoomDisplay(currentZoom);

            if (currentDensity === 'normal') {
                restoreOriginalContent();
                content.style.zoom = currentZoom.toFixed(3);
                paginateMultiPage();
            }
        }

        function resetZoom() {
            applyFitAndPagination();
        }

        function triggerPrint() {
            applyFitAndPagination();
            window.print();
        }

        // Initialize on load
        window.addEventListener('DOMContentLoaded', function () {
            originalContentHTML = document.getElementById('letterContent').innerHTML;

            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.has('fit')) {
                try {
                    const saved = localStorage.getItem('letter_print_density');
                    if (saved && (saved === 'normal' || saved === 'compact' || saved === 'ultra')) {
                        setDensity(saved);
                        return;
                    }
                } catch (e) {}
            }
            applyFitAndPagination();
        });

        window.addEventListener('load', function () {
            applyFitAndPagination();

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('noprint') !== '1') {
                setTimeout(function () {
                    window.print();
                }, 400);
            }
        });
    </script>
</body>

</html>