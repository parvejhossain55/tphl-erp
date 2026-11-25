<?php
/**
 * Shared Project / Category Branding Resolver for Client Statements & Receipts
 * Resolves branding dynamically for:
 *  - Category 10 / Olympus  -> The Olympus Real Estate (Gold #c58b42 & Charcoal #3e3b42)
 *  - Category 9 / Project 74 / Nature -> The Nature (Sky Blue #0284c7 / #0369a1)
 *  - Default -> The Premium Homes Ltd.
 *
 * Expects $clientDetails (and optionally $payment / $bookingId).
 * Sets: $isOlympusProject, $isNatureProject, $stmtCompanyTitle, $stmtCompanyAddress,
 *       $stmtCompanyPhone, $stmtCompanyEmail, $stmtCompanyWebsite, $stmtLogoSrc,
 *       $stmtThemePrimary, $stmtThemeDark, $stmtThemeBgLight, $stmtThemeBorder
 */

$__projId   = !empty($clientDetails['project_id']) ? (int)$clientDetails['project_id'] : (!empty($payment['project_id']) ? (int)$payment['project_id'] : 0);
$__projName = !empty($clientDetails['project_name']) ? trim($clientDetails['project_name']) : (!empty($payment['project_name']) ? trim($payment['project_name']) : '');
$__catId    = !empty($clientDetails['category_id']) ? (int)$clientDetails['category_id'] : (!empty($payment['category_id']) ? (int)$payment['category_id'] : 0);
$__catName  = !empty($clientDetails['category_name']) ? trim($clientDetails['category_name']) : (!empty($payment['category_name']) ? trim($payment['category_name']) : '');

$__dbConn = (isset($this) && isset($this->db))
    ? $this->db
    : ($GLOBALS['db'] ?? (class_exists('Database') ? Database::getInstance()->getConnection() : null));

if ($__dbConn) {
    try {
        $__bkId = !empty($clientDetails['booking_id']) ? (int)$clientDetails['booking_id'] : (!empty($bookingId) ? (int)$bookingId : (!empty($payment['booking_id']) ? (int)$payment['booking_id'] : 0));
        if ($__projId <= 0 && $__bkId > 0) {
            $__st = $__dbConn->prepare("
                SELECT lb.project_id, p.name AS project_name, p.category_id, pc.name AS category_name
                FROM lead_bookings lb
                LEFT JOIN projects p ON lb.project_id = p.id
                LEFT JOIN project_categories pc ON p.category_id = pc.id
                WHERE lb.id = ? LIMIT 1
            ");
            if ($__st) {
                $__st->bind_param('i', $__bkId);
                $__st->execute();
                if ($__row = $__st->get_result()->fetch_assoc()) {
                    $__projId = (int)($__row['project_id'] ?? 0);
                    if (empty($__projName)) $__projName = trim($__row['project_name'] ?? '');
                    if ($__catId <= 0) $__catId = (int)($__row['category_id'] ?? 0);
                    if (empty($__catName)) $__catName = trim($__row['category_name'] ?? '');
                }
                $__st->close();
            }
        } elseif ($__projId > 0 && ($__catId <= 0 || empty($__catName) || empty($__projName))) {
            $__st = $__dbConn->prepare("
                SELECT p.name AS project_name, p.category_id, pc.name AS category_name
                FROM projects p
                LEFT JOIN project_categories pc ON p.category_id = pc.id
                WHERE p.id = ? LIMIT 1
            ");
            if ($__st) {
                $__st->bind_param('i', $__projId);
                $__st->execute();
                if ($__row = $__st->get_result()->fetch_assoc()) {
                    if (empty($__projName)) $__projName = trim($__row['project_name'] ?? '');
                    if ($__catId <= 0) $__catId = (int)($__row['category_id'] ?? 0);
                    if (empty($__catName)) $__catName = trim($__row['category_name'] ?? '');
                }
                $__st->close();
            }
        }
    } catch (Exception $e) {}
}

$isNatureProject = ($__projId === 74)
    || ($__catId === 9)
    || (stripos($__catName, 'The Nature') !== false)
    || (stripos($__projName, 'The Nature') !== false)
    || (stripos($__projName, 'Nature') !== false);

$isOlympusProject = ($__catId === 10)
    || (stripos($__catName, 'Olympus') !== false)
    || (stripos($__projName, 'Olympus') !== false);

$__uploadsDir = dirname(dirname(dirname(dirname(__DIR__)))) . '/public/uploads/';
$__baseUrl    = $GLOBALS['base_url'] ?? '';

if ($isOlympusProject) {
    $stmtLogoSrc = '';
    foreach (['Olympus-logo.png', 'Olympus-logo.jpeg', 'olympus-logo.png', 'olympus-logo.jpeg'] as $__cand) {
        if (file_exists($__uploadsDir . $__cand)) {
            $__ext  = strtolower(pathinfo($__cand, PATHINFO_EXTENSION));
            $__mime = ($__ext === 'jpg' || $__ext === 'jpeg') ? 'image/jpeg' : 'image/png';
            $stmtLogoSrc = 'data:' . $__mime . ';base64,' . base64_encode(file_get_contents($__uploadsDir . $__cand));
            break;
        }
    }
    if (empty($stmtLogoSrc)) {
        $stmtLogoSrc = $__baseUrl . '/public/uploads/Olympus-logo.png';
    }

    $stmtCompanyTitle   = !empty($__catName) ? $__catName : 'The Olympus Real Estate';
    $stmtCompanyAddress = '23/2, SEL HUQ Skypark, 4th floor, Opposite of Wonderland (Shyamoli), Dhaka';
    $stmtCompanyPhone   = '01958-253302';
    $stmtCompanyEmail   = 'info@theolympusrealestate.com';
    $stmtCompanyWebsite = 'www.theolympusrealestate.com';

    $stmtThemePrimary   = '#c58b42';
    $stmtThemeDark      = '#3e3b42';
    $stmtThemeBgLight   = '#fdfbf7';
    $stmtThemeBorder    = '#ebd8c1';
} elseif ($isNatureProject) {
    $__natureLogoFile = $__uploadsDir . 'nature-logo.png';
    if (file_exists($__natureLogoFile)) {
        $stmtLogoSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($__natureLogoFile));
    } else {
        $stmtLogoSrc = $__baseUrl . '/public/uploads/nature-logo.png';
    }

    $stmtCompanyTitle   = !empty($__catName) ? $__catName : 'The Nature';
    $stmtCompanyAddress = "Marine Drive, Cox's Bazar | Corporate Office: Land View Commercial Center, 9th Floor, 28 Gulshan North C/A, Gulshan Circle-2, Dhaka";
    $stmtCompanyPhone   = '+8801958253303';
    $stmtCompanyEmail   = 'accounts@dpremiumhomes.com';
    $stmtCompanyWebsite = '';

    $stmtThemePrimary   = '#0284c7';
    $stmtThemeDark      = '#0369a1';
    $stmtThemeBgLight   = '#f0f9ff';
    $stmtThemeBorder    = '#bae6fd';
} else {
    $stmtLogoSrc        = '';
    $stmtCompanyTitle   = function_exists('getSetting') ? getSetting('website_title', 'The Premium Homes Ltd.') : 'The Premium Homes Ltd.';
    $stmtCompanyAddress = 'Land View Commercial Center, 9th Floor, 28 Gulshan North C/A, Gulshan Circle-2, Dhaka';
    $stmtCompanyPhone   = '+8801958253300';
    $stmtCompanyEmail   = 'info@dpremiumhomes.com';
    $stmtCompanyWebsite = '';

    $stmtThemePrimary   = '#0b6638';
    $stmtThemeDark      = '#0f172a';
    $stmtThemeBgLight   = '#f0fdf4';
    $stmtThemeBorder    = '#bbf7d0';
}
