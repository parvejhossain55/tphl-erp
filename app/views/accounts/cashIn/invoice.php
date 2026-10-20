<?php
// Disable layout for standalone printable invoice
$disable_layout = true;

// -------------------------------------------------------------
// Project Resolution & The Nature (ID: 74) Custom Branding
// -------------------------------------------------------------
$currentProjectId = !empty($clientDetails['project_id']) ? (int)$clientDetails['project_id'] : (!empty($payment['project_id']) ? (int)$payment['project_id'] : 0);
$currentProjectName = !empty($clientDetails['project_name']) ? trim($clientDetails['project_name']) : (!empty($payment['project_name']) ? trim($payment['project_name']) : '');

// Fallback check from booking if project_id not set
if ($currentProjectId <= 0 && !empty($payment['booking_id']) && isset($this) && isset($this->db)) {
    try {
        $pStmt = $this->db->prepare("SELECT project_id FROM lead_bookings WHERE id = ? LIMIT 1");
        if ($pStmt) {
            $pStmt->bind_param("i", $payment['booking_id']);
            $pStmt->execute();
            $pRes = $pStmt->get_result();
            if ($pRow = $pRes->fetch_assoc()) {
                $currentProjectId = (int)$pRow['project_id'];
            }
            $pStmt->close();
        }
    } catch (Exception $e) {}
}

$isNatureProject = ($currentProjectId === 74) || (stripos($currentProjectName, 'The Nature') !== false) || (stripos($currentProjectName, 'Nature') !== false);

if ($isNatureProject) {
    // Nature Logo: public/uploads/nature-logo.png
    $natureLogoFile = dirname(dirname(dirname(dirname(__DIR__)))) . '/public/uploads/nature-logo.png';
    if (file_exists($natureLogoFile)) {
        $logoSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($natureLogoFile));
    } else {
        $logoSrc = $GLOBALS['base_url'] . '/public/uploads/nature-logo.png';
    }

    $companyTitle   = "The Nature";
    $brandDomain    = "The Nature";
    $companyAddress = "Marine Drive, Cox's Bazar | Corporate Office: Land View Commercial Center, 9th Floor, 28 Gulshan North C/A, Gulshan Circle-2, Dhaka";
    $companyPhone   = "+8801958253303";
    $companyEmail   = "accounts@dpremiumhomes.com";

    // Nature Sky/Ocean Blue Brand Palette (from nature-logo.png)
    $themePrimary   = "#0284c7"; // Sky 600
    $themeDark      = "#0369a1"; // Sky 700
    $themeDeep      = "#075985"; // Sky 800
    $themeLight     = "#38bdf8"; // Sky 400
    $themeBgLight   = "#f0f9ff"; // Sky 50
    $themeBadgeBg   = "#e0f2fe"; // Sky 100
    $themeBorder    = "#bae6fd"; // Sky 200
    $themeRgb       = [2, 132, 199];
    $themeQrHex     = "2-132-199";
} else {
    // Retrieve Site Settings for Logo, Phone, Email & Address
    $whiteLogo = function_exists('getSetting') ? (getSetting('white_logo') ?: (getSetting('dark_logo') ?: getSetting('logo'))) : null;
    $logoFileName = !empty($whiteLogo) ? $whiteLogo : '6948f14a9fb8c_1766388042.png';
    $logoUrl = ($whiteLogo && function_exists('getImage')) ? getImage('settings', $whiteLogo) : '';
    if (empty($logoUrl) || strpos($logoUrl, 'avatar.png') !== false) {
        $logoUrl = $GLOBALS['base_url'] . '/public/uploads/settings/' . $logoFileName;
    }
    $logoFilePath = dirname(dirname(dirname(dirname(__DIR__)))) . '/public/uploads/settings/' . $logoFileName;
    $logoSrc = $logoUrl;
    if (file_exists($logoFilePath)) {
        $ext = strtolower(pathinfo($logoFilePath, PATHINFO_EXTENSION));
        $mime = ($ext === 'svg') ? 'image/svg+xml' : 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);
        $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoFilePath));
    }

    $companyTitle   = function_exists('getSetting') ? getSetting('website_title', 'The Premium Homes Ltd.') : 'The Premium Homes Ltd.';
    $brandDomain    = "Real Estate & Property Management";
    $companyPhone   = function_exists('getSetting') ? getSetting('phone', '+8801958253303') : '+8801958253303';
    $companyEmail   = function_exists('getSetting') ? getSetting('email', 'accounts@dpremiumhomes.com') : 'accounts@dpremiumhomes.com';
    $companyAddress = "Land View Commercial Center, 9th Floor, 28 Gulshan North C/A, Gulshan Circle-2, Dhaka";

    // Default Premium Homes Emerald Green Palette
    $themePrimary   = "#0b6638";
    $themeDark      = "#0b4022";
    $themeDeep      = "#0f3b25";
    $themeLight     = "#10b981";
    $themeBgLight   = "#f0fdf4";
    $themeBadgeBg   = "#ecfdf5";
    $themeBorder    = "#bbf7d0";
    $themeRgb       = [11, 102, 56];
    $themeQrHex     = "11-102-56";
}

// Payment & Verification Status
$isVerified = isset($payment['verification_status']) && strtolower(trim($payment['verification_status'])) === 'verified';

// -------------------------------------------------------------
// 1. CREATED BY RESOLUTION:
// Must show sales officer (sales_by_id) or assigned officer (assigned_id)
// -------------------------------------------------------------
$salesPersonName = '';
$salesPersonEmpId = '';

if (!empty($payment['sales_by_name'])) {
    $salesPersonName = trim($payment['sales_by_name']);
    $salesPersonEmpId = trim($payment['sales_by_employee_id'] ?? '');
} elseif (!empty($clientDetails['agent_name'])) {
    $salesPersonName = trim($clientDetails['agent_name']);
    $salesPersonEmpId = trim($clientDetails['agent_employee_id'] ?? '');
}

// Fallback direct DB lookup for sales_by_id or assigned_id if not resolved
if (empty($salesPersonName) && !empty($payment['booking_id']) && isset($this) && isset($this->db)) {
    try {
        $sStmt = $this->db->prepare("
            SELECT u.full_name, 
                   COALESCE(NULLIF(e1.employee_id, ''), NULLIF(e2.employee_id, ''), NULLIF(u.employee_id, '')) as employee_id
            FROM lead_bookings lb
            LEFT JOIN leads l ON lb.lead_id = l.id
            LEFT JOIN users u ON COALESCE(NULLIF(lb.sales_by_id, 0), NULLIF(l.assigned_id, 0)) = u.id
            LEFT JOIN employees e1 ON e1.user_id = u.id
            LEFT JOIN employees e2 ON u.employee_id = e2.id
            WHERE lb.id = ?
            LIMIT 1
        ");
        if ($sStmt) {
            $sStmt->bind_param("i", $payment['booking_id']);
            $sStmt->execute();
            $sRes = $sStmt->get_result();
            if ($sRow = $sRes->fetch_assoc()) {
                $salesPersonName = trim($sRow['full_name'] ?? '');
                $salesPersonEmpId = trim($sRow['employee_id'] ?? '');
            }
            $sStmt->close();
        }
    } catch (Exception $e) {}
}

if (!empty($salesPersonName)) {
    $createdByName = $salesPersonName;
    $createdByEmpId = $salesPersonEmpId;
} else {
    $createdByName = !empty($payment['created_by_name']) ? $payment['created_by_name'] : 'System';
    $createdByEmpId = !empty($payment['created_by_employee_id']) ? trim($payment['created_by_employee_id']) : '';
}

// Format Created By display (no ugly 'No ID')
$createdByDisplay = htmlspecialchars($createdByName) . (!empty($createdByEmpId) ? ' (' . htmlspecialchars($createdByEmpId) . ')' : '');

// -------------------------------------------------------------
// 2. VERIFIED BY RESOLUTION:
// - Accounts Head (30), Accounts (6), Accounts executive (36) -> show user name (+ emp ID)
// - Management role (1, 3) / Management name -> show 'Accounts' (never 'Management')
// -------------------------------------------------------------
$vRoleId = (int)($payment['verified_by_role_id'] ?? 0);
$vRoleName = trim($payment['verified_by_role_name'] ?? '');
$vFullName = trim($payment['verified_by_name'] ?? '');
$vEmpId = trim($payment['verified_by_employee_id'] ?? '');

// If verifier details not populated, look up from DB
if ($isVerified && empty($vRoleName) && !empty($payment['verified_by']) && isset($this) && isset($this->db)) {
    try {
        $vStmt = $this->db->prepare("
            SELECT u.id, u.full_name, u.role_id, r.name as role_name,
                   COALESCE(NULLIF(e1.employee_id, ''), NULLIF(e2.employee_id, ''), NULLIF(u.employee_id, '')) as employee_id
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN employees e1 ON e1.user_id = u.id
            LEFT JOIN employees e2 ON u.employee_id = e2.id
            WHERE u.id = ?
            LIMIT 1
        ");
        if ($vStmt) {
            $vStmt->bind_param("i", $payment['verified_by']);
            $vStmt->execute();
            $vRes = $vStmt->get_result();
            if ($vRow = $vRes->fetch_assoc()) {
                $vRoleId = (int)($vRow['role_id'] ?? 0);
                $vRoleName = trim($vRow['role_name'] ?? '');
                $vFullName = trim($vRow['full_name'] ?? '');
                $vEmpId = trim($vRow['employee_id'] ?? '');
            }
            $vStmt->close();
        }
    } catch (Exception $e) {}
}

if ($isVerified) {
    $isAccountsRole = in_array($vRoleId, [6, 30, 36]) || (stripos($vRoleName, 'account') !== false);
    $isManagementRole = in_array($vRoleId, [1, 3]) || (stripos($vFullName, 'management') !== false) || (stripos($vRoleName, 'admin') !== false);

    if ($isAccountsRole) {
        // Accounts role: show actual user name and employee ID if available
        $verifiedByName = !empty($vFullName) ? $vFullName : 'Accounts Officer';
        $verifiedByEmpId = $vEmpId;
        $verifiedByDisplay = htmlspecialchars($verifiedByName) . (!empty($verifiedByEmpId) ? ' (' . htmlspecialchars($verifiedByEmpId) . ')' : '');
    } elseif ($isManagementRole || strtolower($vFullName) === 'management') {
        // Management role: display 'Accounts' instead of 'Management'
        $verifiedByName = 'Accounts';
        $verifiedByEmpId = '';
        $verifiedByDisplay = 'Accounts';
    } else {
        // Fallback for other verified users
        $verifiedByName = !empty($vFullName) ? $vFullName : 'Accounts';
        $verifiedByEmpId = $vEmpId;
        $verifiedByDisplay = htmlspecialchars($verifiedByName) . (!empty($verifiedByEmpId) ? ' (' . htmlspecialchars($verifiedByEmpId) . ')' : '');
    }
} else {
    $verifiedByName = 'Pending';
    $verifiedByEmpId = '';
    $verifiedByDisplay = 'Pending';
}

// Verification timestamp
$verifiedDateFormatted = 'Awaiting Verification';
if ($isVerified) {
    if (!empty($payment['verified_at'])) {
        $verifiedDateFormatted = date('d M, Y • h:i A', strtotime($payment['verified_at']));
    } elseif (!empty($payment['updated_at']) && $payment['updated_at'] !== '0000-00-00 00:00:00') {
        $verifiedDateFormatted = date('d M, Y • h:i A', strtotime($payment['updated_at']));
    } elseif (!empty($payment['created_at'])) {
        $verifiedDateFormatted = date('d M, Y • h:i A', strtotime($payment['created_at']));
    } else {
        $verifiedDateFormatted = date('d M, Y • h:i A');
    }
}

// Payment Type Label (Removed as requested: only amount is displayed, payment_type like EMI, Booking, Registration, Dev Charge is omitted)
$paymentTypeLabel = '';

// Total amount calculation: Exclude EMI discount amounts so only actual paid money is displayed
$actualPaidPayments = [];
if (!empty($payments) && is_array($payments)) {
    $actualPaidPayments = array_values(array_filter($payments, function($p) {
        return empty($p['is_emi_discount']) || (int)$p['is_emi_discount'] === 0;
    }));
}

if (!empty($isBulk)) {
    $totalAmountPaid = !empty($actualPaidPayments)
        ? array_sum(array_map(fn($p) => (float)($p['amount'] ?? 0), $actualPaidPayments))
        : 0;
} else {
    $isDiscountItem = !empty($payment['is_emi_discount']) && (int)$payment['is_emi_discount'] === 1;
    $totalAmountPaid = $isDiscountItem ? 0 : (float)($payment['amount'] ?? 0);
}

// If bulk has actual paid payments, align primary $payment with the first actual paid payment
if (!empty($actualPaidPayments)) {
    $payment = $actualPaidPayments[0];
}

$amountInWords = function_exists('amountInWords') ? amountInWords($totalAmountPaid) : (class_exists('FormatHelper') ? FormatHelper::amountInWords($totalAmountPaid) : '');

// Bank & Payment Method Display
$paymentMethod = strtoupper($payment['payment_method'] ?? 'CASH');

$isCashPayment = ($paymentMethod === 'CASH');
$isOnlinePayment = ($paymentMethod === 'ONLINE');

if ($isCashPayment) {
    $verifiedLabelMain = 'Cash Verified';
    $pendingLabelMain  = 'Subject to Verification';
    $verifiedCardTitle = 'CASH-VERIFIED PAYMENT RECEIPT';
    $pendingCardTitle  = 'SUBJECT TO VERIFICATION';
    $pendingCardDesc   = 'This cash payment is subject to verification and confirmation.';
    $verifiedStampText = "CASH<br>VERIFIED";
} elseif ($isOnlinePayment) {
    $verifiedLabelMain = 'Online Verified';
    $pendingLabelMain  = 'Subject to Online Verification';
    $verifiedCardTitle = 'ONLINE-VERIFIED PAYMENT RECEIPT';
    $pendingCardTitle  = 'SUBJECT TO ONLINE VERIFICATION';
    $pendingCardDesc   = 'This online payment is subject to verification and confirmation.';
    $verifiedStampText = "ONLINE<br>VERIFIED";
} else {
    $verifiedLabelMain = 'Bank Verified';
    $pendingLabelMain  = 'Subject to Bank Verification';
    $verifiedCardTitle = 'BANK-VERIFIED PAYMENT RECEIPT';
    $pendingCardTitle  = 'SUBJECT TO BANK VERIFICATION';
    $pendingCardDesc   = 'This payment is subject to bank verification and confirmation.';
    $verifiedStampText = "BANK<br>VERIFIED";
}

$displayBank = !empty($payment['company_bank_name']) 
    ? $payment['company_bank_name'] 
    : (!empty($payment['bank_name']) 
        ? $payment['bank_name'] 
        : ($paymentMethod === 'CASH' ? 'Cash in Hand / Office' : 'N/A'));

// Client and Property info
$clientName = !empty($clientDetails['client_name']) ? $clientDetails['client_name'] : (!empty($payment['client_name']) ? $payment['client_name'] : 'N/A');
if (!empty($clientDetails['2nd_lead_owner_name'])) {
    $clientName .= ' & ' . trim($clientDetails['2nd_lead_owner_name']);
}
$clientPhone = !empty($clientDetails['phone']) ? $clientDetails['phone'] : (!empty($clientDetails['phone_number']) ? $clientDetails['phone_number'] : 'N/A');
if (!empty($clientDetails['2nd_owner_phone'])) {
    $clientPhone .= ', ' . trim($clientDetails['2nd_owner_phone']);
}
if ($clientPhone !== 'N/A' && strlen($clientPhone) === 10 && substr($clientPhone, 0, 1) === '1') {
    $clientPhone = '0' . $clientPhone;
}
$clientEmail = !empty($clientDetails['email']) ? $clientDetails['email'] : 'N/A';
$projectName = !empty($clientDetails['project_name']) ? $clientDetails['project_name'] : (!empty($payment['project_name']) ? $payment['project_name'] : 'N/A');
$unitName = !empty($clientDetails['unit_name']) ? $clientDetails['unit_name'] : (!empty($payment['unit_name']) ? $payment['unit_name'] : 'N/A');
$flatNo = !empty($clientDetails['flat_no']) ? $clientDetails['flat_no'] : (!empty($payment['flat_no']) ? $payment['flat_no'] : 'N/A');

$receiptNo = '#REC-' . str_pad($payment['id'] ?? '0', 6, '0', STR_PAD_LEFT);
$receiptDate = date('d M, Y', strtotime($payment['payment_date'] ?? 'now'));

// Public Digital Verification URL & Green QR Code (Primary: #0b6638)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// For local testing: if accessing via localhost/127.0.0.1, use LAN IP so mobile phone scanning works over Wi-Fi
$hostOnly = explode(':', $host)[0];
if (in_array($hostOnly, ['localhost', '127.0.0.1'])) {
    $lanIp = getHostByName(getHostName());
    if (!empty($lanIp) && $lanIp !== '127.0.0.1' && filter_var($lanIp, FILTER_VALIDATE_IP)) {
        $port = isset(explode(':', $host)[1]) ? ':' . explode(':', $host)[1] : '';
        $host = $lanIp . $port;
    }
}

$rawPaymentId = (int)($payment['id'] ?? 0);
$verificationToken = substr(hash('sha256', $rawPaymentId . '_' . ($payment['created_at'] ?? 'tphl') . '_TPHL_VERIFY_KEY_2026'), 0, 12);
$verificationUrl = $protocol . $host . $GLOBALS['base_url'] . '/verify-receipt/' . $rawPaymentId . '?token=' . $verificationToken;

$qrImageSrc = '';
if (class_exists('TCPDF2DBarcode')) {
    try {
        $barcode = new TCPDF2DBarcode($verificationUrl, 'QRCODE,M');
        $qrPng = $barcode->getBarcodePngData(4, 4, $themeRgb ?? [11, 102, 56]);
        if ($qrPng && strlen($qrPng) > 50) {
            $qrImageSrc = 'data:image/png;base64,' . base64_encode($qrPng);
        }
    } catch (Exception $e) {}
}
if (empty($qrImageSrc)) {
    $qrColorParam = !empty($themeQrHex) ? $themeQrHex : '11-102-56';
    $qrImageSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&color=' . $qrColorParam . '&data=' . urlencode($verificationUrl);
}

// Banking Information Resolution (Sender Bank vs Receiver Bank & Ref Number)
$senderBankName = '';
$receiverBankName = '';
$receiverHolderName = '';
$receiverAccountNo = '';
$clientAccountNo = !empty($payment['bank_account_no']) && !in_array(strtolower(trim($payment['bank_account_no'])), ['n/a', '0', '00', 'none', 'null']) ? trim($payment['bank_account_no']) : '';
$onlineType = !empty($payment['online_type']) ? trim($payment['online_type']) : '';

$rawBankName = trim($payment['bank_name'] ?? '');
$companyBankName = trim($payment['company_bank_name'] ?? '');
$companyAccountName = trim($payment['company_account_name'] ?? '');
$companyAccountNo = trim($payment['company_account_number'] ?? '');
$bankId = !empty($payment['bank_id']) ? (int)$payment['bank_id'] : null;

// Determine Reference Number & Label
$refLabel = ($paymentMethod === 'CHEQUE') ? 'Cheque No:' : (($paymentMethod === 'ONLINE') ? 'Trx ID / Ref:' : 'Ref / Receipt No:');
$refNumber = !empty($payment['transaction_ref']) ? trim($payment['transaction_ref']) : (!empty($payment['cheque_no']) ? trim($payment['cheque_no']) : '');
if (!empty($payment['transaction_ref']) && !empty($payment['cheque_no']) && $payment['transaction_ref'] !== $payment['cheque_no']) {
    $refNumber = $payment['transaction_ref'] . ' (Chq: ' . $payment['cheque_no'] . ')';
}
if (empty($refNumber) && $isCashPayment) {
    $refNumber = 'REC-' . str_pad($payment['id'] ?? '0', 6, '0', STR_PAD_LEFT);
} elseif (empty($refNumber)) {
    $refNumber = 'N/A';
}

if (!$isCashPayment) {
    // 1. In case of bulk payments, check if any payment in the batch has company bank information
    if (empty($bankId) && !empty($payments) && is_array($payments)) {
        foreach ($payments as $p) {
            if (!empty($p['bank_id'])) {
                $bankId = (int)$p['bank_id'];
                if (empty($companyBankName) && !empty($p['company_bank_name'])) {
                    $companyBankName = trim($p['company_bank_name']);
                }
                if (empty($companyAccountName) && !empty($p['company_account_name'])) {
                    $companyAccountName = trim($p['company_account_name']);
                }
                if (empty($companyAccountNo) && !empty($p['company_account_number'])) {
                    $companyAccountNo = trim($p['company_account_number']);
                }
                break;
            }
        }
    }

    // 2. Fetch company bank accounts list
    $cbList = [];
    if (isset($this) && isset($this->db)) {
        try {
            $cbQuery = $this->db->query("SELECT id, bank_name, account_name, account_number, is_default FROM company_bank_accounts ORDER BY is_default DESC, id ASC");
            if ($cbQuery) {
                while ($cbRow = $cbQuery->fetch_assoc()) {
                    $cbList[] = $cbRow;
                }
            }
        } catch (Exception $e) {}
    }

    // 3. Resolve matched company bank account
    $matchedCb = null;
    if (!empty($bankId)) {
        foreach ($cbList as $cb) {
            if ($cb['id'] == $bankId) {
                $matchedCb = $cb;
                break;
            }
        }
    }

    // If companyBankName was populated via SQL JOIN from model
    if (!$matchedCb && !empty($companyBankName)) {
        foreach ($cbList as $cb) {
            if (strcasecmp(trim($cb['bank_name']), trim($companyBankName)) === 0) {
                if (!empty($companyAccountName) && strcasecmp(trim($cb['account_name']), trim($companyAccountName)) === 0) {
                    $matchedCb = $cb;
                    break;
                }
            }
        }
        if (!$matchedCb) {
            $matchedCb = [
                'bank_name' => $companyBankName,
                'account_name' => !empty($companyAccountName) ? $companyAccountName : 'The Premium Homes Ltd.',
                'account_number' => !empty($companyAccountNo) ? $companyAccountNo : 'N/A'
            ];
        }
    }

    // If legacy hyphenated string, e.g. "DBBL-The Premium It Solution" or "BRAC-MD. Mainul Hasan Dulon"
    if (!$matchedCb && strpos($rawBankName, '-') !== false) {
        $parts = explode('-', $rawBankName, 2);
        $bankPrefix = trim($parts[0]);
        $receiverPart = trim($parts[1]);
        $prefixLower = strtolower($bankPrefix);

        foreach ($cbList as $cb) {
            $cbBankLower = strtolower($cb['bank_name']);
            $prefixMatches = false;
            if (($prefixLower === 'dbbl' || strpos($prefixLower, 'dutch') !== false) && strpos($cbBankLower, 'dutch') !== false) {
                $prefixMatches = true;
            } elseif ($prefixLower === 'brac' && strpos($cbBankLower, 'brac') !== false) {
                $prefixMatches = true;
            } elseif (($prefixLower === 'ebl' || strpos($prefixLower, 'eastern') !== false) && strpos($cbBankLower, 'eastern') !== false) {
                $prefixMatches = true;
            }

            if (stripos($cb['account_name'], $receiverPart) !== false || stripos($receiverPart, $cb['account_name']) !== false) {
                if ($prefixMatches || empty($bankPrefix)) {
                    $matchedCb = $cb;
                    break;
                }
            }
        }

        if (!$matchedCb) {
            foreach ($cbList as $cb) {
                if (stripos($cb['account_name'], $receiverPart) !== false || stripos($receiverPart, $cb['account_name']) !== false) {
                    $matchedCb = $cb;
                    break;
                }
            }
        }

        if (!$matchedCb) {
            $matchedBank = 'Dutch-Bangla Bank PLC';
            if ($prefixLower === 'brac') $matchedBank = 'BRAC Bank PLC';
            elseif ($prefixLower === 'ebl' || strpos($prefixLower, 'eastern') !== false) $matchedBank = 'Eastern Bank PLC';

            $matchedCb = [
                'bank_name' => $matchedBank,
                'account_name' => !empty($receiverPart) ? $receiverPart : 'The Premium Homes Ltd.',
                'account_number' => 'N/A'
            ];
        }

        if ($prefixLower === 'dbbl' || strpos($prefixLower, 'dutch') !== false) {
            $senderBankName = 'Dutch-Bangla Bank (DBBL)';
        } elseif ($prefixLower === 'brac') {
            $senderBankName = 'BRAC Bank';
        } elseif ($prefixLower === 'ebl' || strpos($prefixLower, 'eastern') !== false) {
            $senderBankName = 'Eastern Bank (EBL)';
        } elseif ($prefixLower === 'bkash') {
            $senderBankName = 'bKash';
        } else {
            $senderBankName = $bankPrefix;
        }
    }

    // If rawBankName itself matches a company bank account holder or company bank name
    if (!$matchedCb && !empty($rawBankName)) {
        foreach ($cbList as $cb) {
            if (stripos($rawBankName, $cb['account_name']) !== false || stripos($cb['account_name'], $rawBankName) !== false) {
                $matchedCb = $cb;
                break;
            }
        }
    }

    if ($matchedCb) {
        $receiverBankName = $matchedCb['bank_name'];
        $receiverHolderName = $matchedCb['account_name'];
        $receiverAccountNo = !empty($matchedCb['account_number']) ? $matchedCb['account_number'] : 'N/A';

        if (empty($senderBankName) || $senderBankName === 'N/A') {
            if (!empty($rawBankName) && !in_array(strtolower($rawBankName), ['0', 'n/a', 'none', 'cash']) && stripos($rawBankName, $receiverHolderName) === false) {
                $senderBankName = $rawBankName;
            } else {
                $senderBankName = (strtoupper($paymentMethod) === 'ONLINE' || strtoupper($paymentMethod) === 'CASH') ? 'N/A' : $matchedCb['bank_name'];
            }
        }
    } else {
        // Fallback: rawBankName is client's sending bank (e.g. Sonali Bank PLC)
        $senderBankName = !empty($rawBankName) && !in_array(strtolower($rawBankName), ['0', 'n/a', 'none', 'cash']) ? $rawBankName : 'N/A';

        $defaultCb = null;
        foreach ($cbList as $cb) {
            if (!empty($cb['is_default'])) {
                $defaultCb = $cb;
                break;
            }
        }
        if (!$defaultCb && !empty($cbList)) {
            $defaultCb = $cbList[0];
        }

        if ($defaultCb) {
            $receiverBankName = $defaultCb['bank_name'];
            $receiverHolderName = $defaultCb['account_name'];
            $receiverAccountNo = !empty($defaultCb['account_number']) ? $defaultCb['account_number'] : 'N/A';
        } else {
            $receiverBankName = 'Dutch-Bangla Bank PLC';
            $receiverHolderName = 'The Premium Homes Ltd.';
            $receiverAccountNo = '2771100365424';
        }
    }

    $displayBank = $receiverBankName;
} else {
    $senderBankName = '';
    $receiverBankName = '';
    $receiverHolderName = '';
    $receiverAccountNo = '';
    $displayBank = 'Cash in Hand / Office';
}

if ($isOnlinePayment) {
    $senderBankName = '';
}

require __DIR__ . '/invoice-receipt-template.php';
