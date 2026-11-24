<?php
// API endpoint to fetch User/Client Payment History, Summaries, EMI info & Flat-wise breakdown.
// Returns client info, overall financial summary, and flat-wise properties with payment history & next payment schedules.

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// -------------------------------------------------------------
// Database Connection (Dynamic config / local vs production detection)
// -------------------------------------------------------------
$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) 
           || strpos($_SERVER['HTTP_HOST'] ?? '', '192.168.') !== false
           || php_sapi_name() === 'cli';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 3,
];

$pdo = null;

// 1. Try configuration from app/config/config.php if available
if (file_exists(__DIR__ . '/../app/config/config.php')) {
    try {
        $cfg = require __DIR__ . '/../app/config/config.php';
        if (is_array($cfg) && !empty($cfg['db_name'])) {
            $host = $cfg['db_host'] ?? '127.0.0.1';
            $db   = $cfg['db_name'];
            $user = $cfg['db_user'] ?? 'root';
            $pass = $cfg['db_pass'] ?? '';
            $pdo  = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, $options);
        }
    } catch (\PDOException $e) {
        $pdo = null;
    }
}

// 2. Fallback to standard local / production presets if config didn't connect
if (!$pdo) {
    $credentials = [
        ['127.0.0.1', 'final_erp_db', 'final_erp_user', '#erp_db@TPHL_PASS#'],
        ['127.0.0.1', 'final_erp_db', 'final_erp_user', 'finaltPhl@#5672dhKL%sd10'],
        ['127.0.0.1', 'final_erp_db', 'final_erp_user', '#erp_db@TPHL_PASS'],
        ['127.0.0.1', 'tphl_db', 'root', ''],
        ['localhost', 'tphl_db', 'root', ''],
    ];

    foreach ($credentials as [$h, $d, $u, $p]) {
        try {
            $pdo = new PDO("mysql:host={$h};dbname={$d};charset=utf8mb4", $u, $p, $options);
            break;
        } catch (\PDOException $ex) {
            continue;
        }
    }

    if (!$pdo) {
        echo json_encode([
            'success' => false,
            'message' => 'DB Connection failed: Unable to connect to ERP Database',
            'data'    => null
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

// -------------------------------------------------------------
// Request Parameters Parsing (GET, POST, JSON Body)
// -------------------------------------------------------------
$rawInput = file_get_contents('php://input');
$jsonBody = !empty($rawInput) ? json_decode($rawInput, true) : [];

// Accept lead_id (supports lead_id or id)
$leadId = (int)($_GET['lead_id'] ?? $_GET['user_id'] ?? $_GET['id'] ?? $_POST['lead_id'] ?? $_POST['user_id'] ?? $_POST['id'] ?? $jsonBody['lead_id'] ?? $jsonBody['user_id'] ?? $jsonBody['id'] ?? 0);
$phone  = trim($_GET['phone'] ?? $_POST['phone'] ?? $jsonBody['phone'] ?? '');

if ($leadId <= 0 && empty($phone)) {
    echo json_encode([
        'success' => false,
        'message' => 'Lead ID is required (e.g. ?lead_id=12 or POST {"lead_id": 12})',
        'data'    => null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    // ---------------------------------------------------------
    // 1. Fetch Client Profile by Lead ID (leads table)
    // ---------------------------------------------------------
    $lead = null;

    if ($leadId > 0) {
        $leadStmt = $pdo->prepare("SELECT * FROM leads WHERE id = ? LIMIT 1");
        $leadStmt->execute([$leadId]);
        $lead = $leadStmt->fetch();
    } elseif (!empty($phone)) {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $leadStmt = $pdo->prepare("SELECT * FROM leads WHERE phone LIKE ? OR phone_number LIKE ? LIMIT 1");
        $leadStmt->execute(["%$cleanPhone%", "%$cleanPhone%"]);
        $lead = $leadStmt->fetch();
        if ($lead) {
            $leadId = (int)$lead['id'];
        }
    }

    if (!$lead) {
        echo json_encode([
            'success' => false,
            'message' => "Client not found in leads table for Lead ID: {$leadId}",
            'data'    => null
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // Client Info Object (Strictly Lead ID based)
    $clientInfo = [
        'lead_id'        => $leadId,
        'client_name'    => $lead['full_name'] ?? 'Client',
        'phone'          => !empty($lead['phone']) ? $lead['phone'] : ($lead['phone_number'] ?? ''),
        'email'          => $lead['email'] ?? '',
        'company'        => $lead['company'] ?? '',
        'designation'    => $lead['designation'] ?? '',
        'present_address'=> $lead['present_address'] ?? '',
    ];

    // ---------------------------------------------------------
    // 2. Fetch All Active Bookings / Flats for this Lead
    // ---------------------------------------------------------
    // Check column existence dynamically to ensure compatibility across DB schemas
    $checkCol = function($table, $col) use ($pdo) {
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $stmt->execute([$col]);
            return ($stmt->fetch() !== false);
        } catch (\Exception $e) {
            return false;
        }
    };

    $handoverSql   = $checkCol('projects', 'handover') ? "p.handover AS project_handover," : "NULL AS project_handover,";
    $isShopSql     = $checkCol('flats', 'is_shop') ? "f.is_shop," : "0 AS is_shop,";
    $unitTypeSql   = $checkCol('flats', 'unit_type') ? "f.unit_type," : "'residential' AS unit_type,";
    $devChargeSql  = $checkCol('flats', 'development_charges') ? "f.development_charges," : "0 AS development_charges,";
    $totalEmiLbSql = $checkCol('lead_bookings', 'total_emi_no') ? "lb.total_emi_no," : "NULL AS total_emi_no,";

    $bSql = "
        SELECT 
            lb.*,
            {$totalEmiLbSql}
            p.id AS proj_id,
            p.name AS project_name,
            p.types AS project_type,
            {$handoverSql}
            p.project_map_location,
            COALESCE(loc.name, '') AS location_name,
            f.id AS f_id,
            f.flat_name,
            f.floor_no,
            f.sqft,
            f.price_value,
            f.land_share_value,
            {$devChargeSql}
            {$isShopSql}
            {$unitTypeSql}
            lb.id AS id
        FROM lead_bookings lb
        LEFT JOIN projects p ON lb.project_id = p.id
        LEFT JOIN locations loc ON p.location_id = loc.id
        LEFT JOIN flats f ON lb.flat_id = f.id
        WHERE lb.lead_id = ?
          AND lb.deleted_at IS NULL
          AND lb.booking_status NOT IN ('cancelled', 'refunded')
        ORDER BY lb.id DESC
    ";

    $bStmt = $pdo->prepare($bSql);
    $bStmt->execute([$leadId]);
    $bookings = $bStmt->fetchAll();

    // Human-friendly label mapping for payment types
    $paymentTypeLabels = [
        'booking_money'       => 'Booking Money',
        'registration'        => 'Down Payment / Registration',
        'land_share'          => 'Land Share',
        'development_charge'  => 'Development Charge',
        'emi'                 => 'Monthly Installment (EMI)',
        'emi_due_settlement'  => 'EMI Due Settlement',
        'mutation_cost'       => 'Mutation Cost',
        'shop_service_charge' => 'Shop Service Charge',
        'others'              => 'Other Payment'
    ];

    $propertiesList    = [];
    $overallTotalPrice = 0.0;
    $overallDiscount   = 0.0;
    $overallNetPayable = 0.0;
    $overallTotalPaid  = 0.0;
    $overallTotalDue   = 0.0;

    // ---------------------------------------------------------
    // 3. Process Each Booking / Flat
    // ---------------------------------------------------------
    foreach ($bookings as $b) {
        $bookingId = (int)$b['id'];

        // Determine price
        $flatPrice = (float)($b['price_value'] ?? 0);
        $agreementValue = (float)($b['agreement_value'] ?? 0);
        $totalPrice = ($flatPrice > 0) ? $flatPrice : $agreementValue;
        $discountAmount = (float)($b['discount_amount'] ?? 0);
        $netPayable = max(0, $totalPrice - $discountAmount);

        // Fetch Payments for this booking
        $pStmt = $pdo->prepare("
            SELECT 
                lp.*,
                es.emi_no AS sched_emi_no,
                es.due_date AS sched_due_date,
                es.emi_amount AS sched_emi_amount
            FROM lead_payments lp
            LEFT JOIN emi_schedules es ON lp.id = es.lead_payment_id
            WHERE lp.booking_id = ? 
              AND lp.is_trashed = 0
            ORDER BY COALESCE(lp.payment_date, lp.created_at) ASC, lp.id ASC
        ");
        $pStmt->execute([$bookingId]);
        $payments = $pStmt->fetchAll();

        $totalPaidFlat       = 0.0;
        $bulkEmiDiscountFlat = 0.0;
        $paymentHistory      = [];

        foreach ($payments as $p) {
            $amt = (float)$p['amount'];
            $isEmiDiscount = (!empty($p['is_emi_discount']) && (int)$p['is_emi_discount'] === 1);
            $vStatus = $p['verification_status'] ?? 'pending';
            $pStatus = $p['payment_status'] ?? 'paid';

            if ($vStatus !== 'rejected') {
                if ($isEmiDiscount) {
                    $bulkEmiDiscountFlat += $amt;
                } else if ($vStatus === 'verified') {
                    $totalPaidFlat += $amt;
                }

                if (!empty($p['bulk_emi_discount']) && (float)$p['bulk_emi_discount'] > 0) {
                    $bulkEmiDiscountFlat += (float)$p['bulk_emi_discount'];
                }
            }

            // Clean Payment History Record
            $pType = $p['payment_type'] ?? 'others';
            $paymentHistory[] = [
                'payment_id'          => (int)$p['id'],
                'receipt_no'          => !empty($p['transaction_ref']) ? $p['transaction_ref'] : 'N/A',
                'payment_date'        => !empty($p['payment_date']) ? $p['payment_date'] : substr($p['created_at'] ?? '', 0, 10),
                'payment_type'        => $pType,
                'payment_type_label'  => $paymentTypeLabels[$pType] ?? ucwords(str_replace('_', ' ', $pType)),
                'amount'              => (float)$p['amount'],
                'payment_method'      => !empty($p['payment_method']) ? ucfirst($p['payment_method']) : 'Cash',
                'bank_name'           => $p['bank_name'] ?? null,
                'cheque_no'           => $p['cheque_no'] ?? null,
                'emi_no'              => !empty($p['emi_no']) ? (int)$p['emi_no'] : (!empty($p['sched_emi_no']) ? (int)$p['sched_emi_no'] : null),
                'payment_status'      => $pStatus,
                'verification_status' => $vStatus,
                'is_discount'         => $isEmiDiscount,
                'penalty_amount'      => (float)($p['penalty_amount'] ?? 0),
                'penalty_paid'        => (float)($p['penalty_paid'] ?? 0),
                'service_fee'         => (float)($p['service_fee'] ?? 0),
            ];
        }

        // Net Due for this flat
        $totalDueFlat = max(0, $netPayable - $totalPaidFlat - $bulkEmiDiscountFlat);

        $paidPercentage = ($netPayable > 0) 
            ? min(100, round(($totalPaidFlat / $netPayable) * 100, 1)) . '%' 
            : '100%';

        // -----------------------------------------------------
        // EMI Statistics & Schedules
        // -----------------------------------------------------
        // 1. Total EMI Count
        $totalEmiNo = (int)($b['total_emi_no'] ?? 0);
        if ($totalEmiNo <= 0) {
            $maxEmiStmt = $pdo->prepare("SELECT MAX(total_emi_no) as max_tot FROM lead_payments WHERE booking_id = ? AND is_trashed = 0 AND total_emi_no IS NOT NULL");
            $maxEmiStmt->execute([$bookingId]);
            $maxEmiRow = $maxEmiStmt->fetch();
            $totalEmiNo = (int)($maxEmiRow['max_tot'] ?? 0);
        }
        if ($totalEmiNo <= 0) {
            $maxSchedStmt = $pdo->prepare("SELECT MAX(emi_no) as max_emi FROM emi_schedules WHERE lead_booking_id = ?");
            $maxSchedStmt->execute([$bookingId]);
            $maxSchedRow = $maxSchedStmt->fetch();
            $totalEmiNo = (int)($maxSchedRow['max_emi'] ?? 0);
        }
        if ($totalEmiNo <= 0) {
            $totalEmiNo = 36; // Standard default plan
        }

        // 2. Completed EMIs count
        $compEmiStmt = $pdo->prepare("
            SELECT COUNT(DISTINCT es.emi_no) as cnt 
            FROM emi_schedules es 
            LEFT JOIN lead_payments lp ON es.lead_payment_id = lp.id
            WHERE es.lead_booking_id = ? 
              AND (es.status = 'paid' OR (lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' AND lp.is_trashed = 0))
        ");
        $compEmiStmt->execute([$bookingId]);
        $completedEmiCount = (int)($compEmiStmt->fetch()['cnt'] ?? 0);
        $pendingEmiCount   = max(0, $totalEmiNo - $completedEmiCount);

        // Per EMI estimate
        $constructionFee = max(0, $totalPrice - ((float)($b['land_share_value'] ?? 0) + (float)($b['development_charges'] ?? 0)));
        $perEmiAmount = ($totalEmiNo > 0 && $constructionFee > 0) ? round($constructionFee / $totalEmiNo, 2) : 0.0;

        // 3. Next Payment Due & Date
        $nextPaymentDue  = 0.0;
        $nextPaymentDate = 'Fully Paid';

        $nextEmiStmt = $pdo->prepare("
            SELECT emi_no, emi_amount, due_date 
            FROM emi_schedules 
            WHERE lead_booking_id = ? AND status IN ('pending', 'due')
            ORDER BY due_date ASC 
            LIMIT 1
        ");
        $nextEmiStmt->execute([$bookingId]);
        $nextEmi = $nextEmiStmt->fetch();

        if ($nextEmi) {
            $nextPaymentDue  = (float)$nextEmi['emi_amount'];
            $nextPaymentDate = (string)$nextEmi['due_date'];
        } else {
            // Check for pending row in lead_payments
            $pendingPayStmt = $pdo->prepare("
                SELECT amount, payment_date 
                FROM lead_payments 
                WHERE booking_id = ? AND payment_status = 'pending' AND is_trashed = 0 
                ORDER BY payment_date ASC 
                LIMIT 1
            ");
            $pendingPayStmt->execute([$bookingId]);
            $pendingPay = $pendingPayStmt->fetch();

            if ($pendingPay) {
                $nextPaymentDue  = (float)$pendingPay['amount'];
                $nextPaymentDate = (string)$pendingPay['payment_date'];
            } elseif ($totalDueFlat > 0) {
                $nextPaymentDue  = $totalDueFlat;
                $nextPaymentDate = !empty($b['project_handover']) ? (string)$b['project_handover'] : 'On Handover';
            } else {
                $nextPaymentDue  = 0.0;
                $nextPaymentDate = 'Fully Paid';
            }
        }

        // Location text
        $locationStr = !empty($b['location_name']) 
            ? $b['location_name'] 
            : (!empty($b['project_map_location']) ? $b['project_map_location'] : '');

        // Flat Info
        $flatInfo = [
            'flat_id'          => (int)($b['flat_id'] ?? 0),
            'flat_name'        => $b['flat_name'] ?? 'N/A',
            'floor_no'         => $b['floor_no'] ?? 'N/A',
            'size_sqft'        => (float)($b['sqft'] ?? 0),
            'is_shop'          => (bool)($b['is_shop'] ?? false),
            'unit_type'        => $b['unit_type'] ?? 'residential',
            'project_id'       => (int)($b['project_id'] ?? 0),
            'project_name'     => $b['project_name'] ?? 'N/A',
            'project_type'     => $b['project_type'] ?? 'N/A',
            'project_location' => $locationStr,
            'handover_date'    => $b['project_handover'] ?? 'N/A',
        ];

        // Financial Summary for this property
        $financialSummary = [
            'total_price'       => $totalPrice,
            'agreement_value'   => $agreementValue,
            'discount_amount'   => $discountAmount,
            'bulk_emi_discount' => $bulkEmiDiscountFlat,
            'net_payable'       => $netPayable,
            'total_paid'        => $totalPaidFlat,
            'total_due'         => $totalDueFlat,
            'paid_percentage'   => $paidPercentage,
        ];

        // EMI Summary for this property
        $emiInfo = [
            'total_emi_count'     => $totalEmiNo,
            'completed_emi_count' => $completedEmiCount,
            'pending_emi_count'   => $pendingEmiCount,
            'per_emi_amount'      => $perEmiAmount,
            'next_payment_date'   => $nextPaymentDate,
            'next_payment_amount' => $nextPaymentDue,
        ];

        // Add to overall totals
        $overallTotalPrice += $totalPrice;
        $overallDiscount   += $discountAmount + $bulkEmiDiscountFlat;
        $overallNetPayable += $netPayable;
        $overallTotalPaid  += $totalPaidFlat;
        $overallTotalDue   += $totalDueFlat;

        // Push property object
        $propertiesList[] = [
            'booking_id'        => $bookingId,
            'booking_date'      => !empty($b['booking_date']) ? $b['booking_date'] : 'N/A',
            'booking_status'    => $b['booking_status'] ?? 'active',
            'flat_info'         => $flatInfo,
            'financial_summary' => $financialSummary,
            'emi_info'          => $emiInfo,
            'payment_history'   => $paymentHistory,
        ];
    }

    // Overall summary across all flats
    $overallSummary = [
        'total_properties' => count($propertiesList),
        'total_price'      => $overallTotalPrice,
        'total_discount'   => $overallDiscount,
        'net_payable'      => $overallNetPayable,
        'total_paid'       => $overallTotalPaid,
        'total_due'        => $overallTotalDue,
        'overall_paid_pct' => ($overallNetPayable > 0) 
            ? min(100, round(($overallTotalPaid / $overallNetPayable) * 100, 1)) . '%' 
            : '100%',
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Payment history and flat details fetched successfully.',
        'data'    => [
            'client_info'     => $clientInfo,
            'overall_summary' => $overallSummary,
            'properties'      => $propertiesList,
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database Query Error: ' . $e->getMessage(),
        'data'    => null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal Server Error: ' . $e->getMessage(),
        'data'    => null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
