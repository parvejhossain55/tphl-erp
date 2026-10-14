<?php
// API endpoint to fetch Project Progress Details by Project ID & User ID
// Returns project info, user financial ledger/dues, construction progress, live cameras, gallery & nearby locations.

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// -------------------------------------------------------------
// Database Connection (Dynamic local vs production detection)
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

if ($isLocal) {
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=tphl_db;charset=utf8mb4", 'root', '', $options);
    } catch (\PDOException $e) {
        try {
            $pdo = new PDO("mysql:host=127.0.0.1;dbname=final_erp_db;charset=utf8mb4", 'final_erp_user', 'finaltPhl@#5672dhKL%sd10', $options);
        } catch (\PDOException $e2) {
            echo json_encode(['success' => false, 'message' => 'DB Connection failed: ' . $e2->getMessage(), 'data' => null]);
            exit;
        }
    }
} else {
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=final_erp_db;charset=utf8mb4", 'final_erp_user', 'finaltPhl@#5672dhKL%sd10', $options);
    } catch (\PDOException $e) {
        try {
            $pdo = new PDO("mysql:host=127.0.0.1;dbname=tphl_db;charset=utf8mb4", 'root', '', $options);
        } catch (\PDOException $e2) {
            echo json_encode(['success' => false, 'message' => 'DB Connection failed: ' . $e2->getMessage(), 'data' => null]);
            exit;
        }
    }
}

// -------------------------------------------------------------
// Helper Functions
// -------------------------------------------------------------
$baseUrl = $isLocal 
    ? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/TPHL-ERP") 
    : 'https://thepremiumhomesltd.com/TPHL-ERP';

$formatImg = function($img) use ($baseUrl) {
    if (empty($img)) return '';
    if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
        return $img;
    }
    return $baseUrl . '/public/uploads/projects/' . ltrim($img, '/');
};

function cleanDescriptionText($desc) {
    if (empty($desc)) return '';
    $cleaned = preg_replace('/<(?:br|p|div|li|h[1-6]|tr|td|th)\s*\/?>/i', ' ', $desc);
    $cleaned = preg_replace('/<\/(?:p|div|li|h[1-6]|tr|td|th)>/i', ' ', $cleaned);
    $cleaned = strip_tags($cleaned);
    $cleaned = html_entity_decode($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $cleaned = str_replace("\xc2\xa0", ' ', $cleaned);
    $cleaned = preg_replace('/\s+/', ' ', $cleaned);
    return trim($cleaned);
}

function parseTotalFloorsString($buildingType) {
    if (empty($buildingType)) return 'N/A';
    if (preg_match_all('/\d+/', $buildingType, $matches)) {
        $numbers = array_map('intval', $matches[0]);
        if (!empty($numbers)) {
            $maxFloor = max($numbers);
            if (stripos($buildingType, 'G+') !== false || stripos($buildingType, 'G +') !== false) {
                return (string)($maxFloor + 1);
            }
            return (string)$maxFloor;
        }
    }
    return $buildingType;
}

// -------------------------------------------------------------
// Request Parameters Parsing
// -------------------------------------------------------------
$rawInput = file_get_contents('php://input');
$jsonBody = !empty($rawInput) ? json_decode($rawInput, true) : [];

// Accept project ID via id / project_id / property_id
$projectId = (int)($_GET['id'] ?? $_GET['project_id'] ?? $_GET['property_id'] ?? $_POST['id'] ?? $_POST['project_id'] ?? $jsonBody['id'] ?? $jsonBody['project_id'] ?? 0);

// Accept user ID via user_id / client_id / lead_id
$userId = (int)($_GET['user_id'] ?? $_GET['client_id'] ?? $_GET['lead_id'] ?? $_POST['user_id'] ?? $_POST['client_id'] ?? $jsonBody['user_id'] ?? $jsonBody['client_id'] ?? 0);

if ($projectId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Project ID is required (e.g. ?id=5&user_id=12)',
        'data'    => null,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    // ---------------------------------------------------------
    // 1. Fetch Project Details
    // ---------------------------------------------------------
    $projStmt = $pdo->prepare("
        SELECT 
            p.*, 
            COALESCE(l.name, '') AS location_name
        FROM projects p
        LEFT JOIN locations l ON p.location_id = l.id
        WHERE p.id = ? AND p.deleted_at IS NULL
        LIMIT 1
    ");
    $projStmt->execute([$projectId]);
    $project = $projStmt->fetch();

    if (!$project) {
        echo json_encode([
            'success' => false,
            'message' => "Project not found for ID {$projectId}",
            'data'    => null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $locationStr = !empty($project['location_name']) ? $project['location_name'] : (!empty($project['project_map_location']) ? $project['project_map_location'] : '');

    // ---------------------------------------------------------
    // 2. Fetch User Financial Ledger / Booking Information
    // ---------------------------------------------------------
    $booking = null;
    $leadId  = 0;

    if ($userId > 0) {
        // Resolve Lead ID from onboard_clients or leads table
        $clientStmt = $pdo->prepare("SELECT lead_id, lead_booking_id, cameraLink1, cameraLink2 FROM onboard_clients WHERE id = ? OR lead_id = ? LIMIT 1");
        $clientStmt->execute([$userId, $userId]);
        $onboard = $clientStmt->fetch();

        if ($onboard && !empty($onboard['lead_id'])) {
            $leadId = (int)$onboard['lead_id'];
        } else {
            $leadId = $userId;
        }

        // Fetch active booking for this project
        $bSql = "
            SELECT * FROM lead_bookings 
            WHERE project_id = ? 
              AND (lead_id = ? " . ($onboard && !empty($onboard['lead_booking_id']) ? " OR id = " . (int)$onboard['lead_booking_id'] : "") . ")
              AND booking_status != 'cancelled'
            ORDER BY id DESC 
            LIMIT 1
        ";
        $bStmt = $pdo->prepare($bSql);
        $bStmt->execute([$projectId, $leadId]);
        $booking = $bStmt->fetch();
    }

    $totalValue          = 0.0;
    $totalPaid           = 0.0;
    $remainingDue        = 0.0;
    $paidAmountPercentage= "0%";
    $nextPaymentDue      = 0.0;
    $nextPaymentDate     = "N/A";
    $bulkEmiDiscount     = 0.0;
    $discountAmount      = 0.0;

    if ($booking) {
        $bookingId       = (int)$booking['id'];
        $totalValue      = (float)($booking['agreement_value'] ?? 0);
        $discountAmount  = (float)($booking['discount_amount'] ?? 0);

        // Fetch payments for this booking
        $pStmt = $pdo->prepare("
            SELECT amount, payment_type, is_emi_discount, bulk_emi_discount, payment_status, payment_date
            FROM lead_payments 
            WHERE booking_id = ? AND is_trashed = 0
        ");
        $pStmt->execute([$bookingId]);
        $payments = $pStmt->fetchAll();

        foreach ($payments as $p) {
            $amt = (float)$p['amount'];
            $isDiscount = (!empty($p['is_emi_discount']) && (int)$p['is_emi_discount'] === 1);

            if ($p['payment_status'] === 'paid') {
                if ($isDiscount) {
                    $bulkEmiDiscount += $amt;
                } else {
                    $totalPaid += $amt;
                }
            }
            if (!empty($p['bulk_emi_discount']) && (float)$p['bulk_emi_discount'] > 0) {
                $bulkEmiDiscount += (float)$p['bulk_emi_discount'];
            }
        }

        $effectiveTotal = max(0, $totalValue - $discountAmount);
        $remainingDue   = max(0, $effectiveTotal - $totalPaid - $bulkEmiDiscount);

        if ($effectiveTotal > 0) {
            $pctNum = min(100, round(($totalPaid / $effectiveTotal) * 100, 1));
            $paidAmountPercentage = $pctNum . '%';
        } else {
            $paidAmountPercentage = "100%";
        }

        // Next Payment Due & Date from emi_schedules
        $emiStmt = $pdo->prepare("
            SELECT emi_amount, due_date 
            FROM emi_schedules 
            WHERE lead_booking_id = ? AND status = 'pending' 
            ORDER BY due_date ASC 
            LIMIT 1
        ");
        $emiStmt->execute([$bookingId]);
        $nextEmi = $emiStmt->fetch();

        if ($nextEmi) {
            $nextPaymentDue  = (float)$nextEmi['emi_amount'];
            $nextPaymentDate = (string)$nextEmi['due_date'];
        } else {
            // Check pending in lead_payments
            $pendingStmt = $pdo->prepare("
                SELECT amount, payment_date 
                FROM lead_payments 
                WHERE booking_id = ? AND payment_status = 'pending' AND is_trashed = 0 
                ORDER BY payment_date ASC 
                LIMIT 1
            ");
            $pendingStmt->execute([$bookingId]);
            $pendingPay = $pendingStmt->fetch();

            if ($pendingPay) {
                $nextPaymentDue  = (float)$pendingPay['amount'];
                $nextPaymentDate = (string)$pendingPay['payment_date'];
            } elseif ($remainingDue > 0) {
                $nextPaymentDue  = $remainingDue;
                $nextPaymentDate = !empty($project['handover']) ? $project['handover'] : 'On Handover';
            } else {
                $nextPaymentDue  = 0.0;
                $nextPaymentDate = 'Fully Paid';
            }
        }
    }

    // ---------------------------------------------------------
    // 3. Construction Progress (Overall & Category-wise)
    // ---------------------------------------------------------
    $progStmt = $pdo->prepare("
        SELECT 
            structural_work,
            plaster_work,
            brick_work,
            electrical_work,
            plumbing_work,
            tiles_work,
            paint_work,
            total_progress,
            last_updated_date
        FROM project_progress
        WHERE project_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $progStmt->execute([$projectId]);
    $progressRow = $progStmt->fetch();

    $formatPct = function($val) {
        $num = (float)$val;
        return (floor($num) == $num ? (int)$num : number_format($num, 1)) . '%';
    };

    if ($progressRow) {
        $totalProgressPct = $formatPct($progressRow['total_progress'] ?? 0);
        $categoryWiseProgress = [
            'structural_work' => $formatPct($progressRow['structural_work'] ?? 0),
            'plaster_work'    => $formatPct($progressRow['plaster_work'] ?? 0),
            'brick_work'      => $formatPct($progressRow['brick_work'] ?? 0),
            'electrical_work' => $formatPct($progressRow['electrical_work'] ?? 0),
            'plumbing_work'   => $formatPct($progressRow['plumbing_work'] ?? 0),
            'tiles_work'      => $formatPct($progressRow['tiles_work'] ?? 0),
            'paint_work'      => $formatPct($progressRow['paint_work'] ?? 0),
        ];
    } else {
        $totalProgressPct = "0%";
        $categoryWiseProgress = [
            'structural_work' => '0%',
            'plaster_work'    => '0%',
            'brick_work'      => '0%',
            'electrical_work' => '0%',
            'plumbing_work'   => '0%',
            'tiles_work'      => '0%',
            'paint_work'      => '0%',
        ];
    }

    // ---------------------------------------------------------
    // 4. Live Cameras
    // ---------------------------------------------------------
    $liveCameraLinks = [];
    $camStmt = $pdo->prepare("
        SELECT id, camera_name, camera_slug, web_link, rtsp_url 
        FROM cameras 
        WHERE project_id = ? AND status = 'active' 
        ORDER BY id ASC
    ");
    $camStmt->execute([$projectId]);
    $cameras = $camStmt->fetchAll();

    foreach ($cameras as $cam) {
        $streamLink = trim(($cam['web_link'] ?? '') ?: ($cam['rtsp_url'] ?? ''));
        if (!empty($streamLink)) {
            $formattedLink = str_starts_with($streamLink, 'http') ? $streamLink : ('https://tphlcamera.xyz/' . ltrim($streamLink, '/'));
            $liveCameraLinks[] = [
                'id'          => (int)$cam['id'],
                'camera_name' => (string)($cam['camera_name'] ?? 'Live Camera'),
                'web_link'    => $formattedLink,
                'rtsp_url'    => (string)($cam['rtsp_url'] ?? ''),
                'url'         => $formattedLink,
            ];
        }
    }

    // Fallback to onboard_clients camera links if cameras table has none
    if (empty($liveCameraLinks) && !empty($onboard)) {
        if (!empty($onboard['cameraLink1'])) {
            $liveCameraLinks[] = [
                'camera_name' => 'Camera 1',
                'web_link'    => $onboard['cameraLink1'],
                'url'         => $onboard['cameraLink1'],
            ];
        }
        if (!empty($onboard['cameraLink2'])) {
            $liveCameraLinks[] = [
                'camera_name' => 'Camera 2',
                'web_link'    => $onboard['cameraLink2'],
                'url'         => $onboard['cameraLink2'],
            ];
        }
    }

    // ---------------------------------------------------------
    // 5. Gallery Images with title and links
    // ---------------------------------------------------------
    $galleryList = [];
    if (!empty($project['gallery_images'])) {
        $rawGallery = json_decode($project['gallery_images'], true);
        if (is_array($rawGallery)) {
            foreach ($rawGallery as $gItem) {
                $gImg = $formatImg($gItem['image'] ?? '');
                if (!empty($gImg)) {
                    $galleryList[] = [
                        'title' => (string)($gItem['title'] ?? 'Gallery Image'),
                        'image' => $gImg,
                        'link'  => $gImg,
                    ];
                }
            }
        }
    }

    // ---------------------------------------------------------
    // 6. Nearby Locations & Nearby Map Images
    // ---------------------------------------------------------
    $nearbyLocationNames = [];
    $nearbyMapImages     = [];
    $nearbyDetailedList  = [];

    if (!empty($project['map_image'])) {
        $mapImgUrl = $formatImg($project['map_image']);
        if (!empty($mapImgUrl)) {
            $nearbyMapImages[] = $mapImgUrl;
        }
    }

    if (!empty($project['nearby_locations'])) {
        $rawNearby = json_decode($project['nearby_locations'], true);
        if (is_array($rawNearby)) {
            foreach ($rawNearby as $nItem) {
                $nName = trim($nItem['name'] ?? '');
                $nDist = trim($nItem['distance'] ?? '');
                $nImg  = $formatImg($nItem['image'] ?? '');

                if (!empty($nName)) {
                    $nearbyLocationNames[] = !empty($nDist) ? "{$nName} ({$nDist})" : $nName;
                }
                if (!empty($nImg)) {
                    $nearbyMapImages[] = $nImg;
                }

                $nearbyDetailedList[] = [
                    'name'      => $nName,
                    'distance'  => $nDist,
                    'map_image' => $nImg,
                    'image'     => $nImg,
                ];
            }
        }
    }

    // ---------------------------------------------------------
    // 7. Handover Date & Formatting
    // ---------------------------------------------------------
    $handoverDate = !empty($project['handover']) 
        ? $project['handover'] 
        : (!empty($project['expected_handover_date']) ? $project['expected_handover_date'] : 'To be announced');

    $frontRoad = !empty($project['front_road']) ? trim($project['front_road']) : '';
    if (!empty($frontRoad) && !preg_match('/ft|feet|m/i', $frontRoad)) {
        $frontRoadFormatted = $frontRoad . ' ft';
    } else {
        $frontRoadFormatted = $frontRoad;
    }

    $plotSizeKatha = !empty($project['plot_size']) ? $project['plot_size'] . ' Katha' : 'N/A';
    $totalFloors   = parseTotalFloorsString($project['building_type'] ?? '');
    $parkingType   = !empty($project['car_parking']) ? 'Basement Parking' : 'Available';

    // ---------------------------------------------------------
    // 8. Final Response Assembly
    // ---------------------------------------------------------
    $responsePayload = [
        // Project Overview
        'project_id'                             => (int)$project['id'],
        'user_id'                                => (int)$userId,
        'project_name'                           => (string)$project['name'],
        'location'                               => (string)$locationStr,
        'description'                            => (string)($project['description'] ?? ''),
        'short_description'                      => cleanDescriptionText($project['description'] ?? ''),

        // Construction Progress
        'total_progress_completion_parcentage'   => $totalProgressPct,
        'total_progress'                         => $totalProgressPct,
        'construction_progress_category_wise'    => $categoryWiseProgress,

        // Handover & Timeline
        'handover_date'                          => (string)$handoverDate,

        // User Financial Ledger / Dues
        'total_value'                            => $totalValue,
        'total_value_formatted'                  => number_format($totalValue, 2) . ' BDT',
        'total_paid'                             => $totalPaid,
        'total_paid_formatted'                   => number_format($totalPaid, 2) . ' BDT',
        'paid_amount_percentage_of_100'          => $paidAmountPercentage,
        'remaining_due'                          => $remainingDue,
        'remaining_due_formatted'                => number_format($remainingDue, 2) . ' BDT',
        'next_payment_due'                       => $nextPaymentDue,
        'next_payment_due_formatted'             => number_format($nextPaymentDue, 2) . ' BDT',
        'next_payment_date'                      => (string)$nextPaymentDate,

        // Live Cameras
        'live_camera_links'                      => $liveCameraLinks,

        // Project Specifications
        'project_land_katha'                     => (string)$plotSizeKatha,
        'total_units'                            => (int)($project['apartment_count'] ?? 0),
        'total_floors'                           => (string)$totalFloors,
        'parking_type'                           => (string)$parkingType,
        'developer_name'                         => 'The Premium Homes Limited',
        'brochure_link'                          => (string)($project['brochure_link'] ?? ''),
        'building_type'                          => (string)($project['building_type'] ?? ''),
        'total_share'                            => (int)($project['total_share'] ?? 0),
        'unit_per_floor'                         => (int)($project['unit_per_floor'] ?? 0),
        'front_road'                             => (string)$frontRoadFormatted,
        'front_road_feet'                        => (string)$frontRoadFormatted,
        'passenger_lift_number'                  => (int)($project['passenger_lift'] ?? 0),

        // Gallery & Map Indications
        'galary_images_with_title_and_links'     => $galleryList,
        'gallery_images'                         => $galleryList,
        'nearby_map_images'                      => $nearbyMapImages,
        'near_by_locations_name'                 => $nearbyLocationNames,
        'nearby_locations'                       => $nearbyDetailedList,
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Project progress details fetched successfully',
        'data'    => $responsePayload,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

} catch (\PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database Query Error: ' . $e->getMessage(),
        'data'    => null,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (\Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server Error: ' . $e->getMessage(),
        'data'    => null,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
