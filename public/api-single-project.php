<?php
// API endpoint to fetch a single project details by ID.
// Supports both local and production environments.

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
$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) 
           || strpos($_SERVER['HTTP_HOST'] ?? '', '192.168.') !== false;
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

function cleanDescription($desc) {
    if (empty($desc)) return '';
    $cleaned = preg_replace('/<(?:br|p|div|li|h[1-6]|tr|td|th)\s*\/?>/i', ' ', $desc);
    $cleaned = preg_replace('/<\/(?:p|div|li|h[1-6]|tr|td|th)>/i', ' ', $cleaned);
    $cleaned = strip_tags($cleaned);
    $cleaned = html_entity_decode($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $cleaned = str_replace("\xc2\xa0", ' ', $cleaned);
    $cleaned = preg_replace('/\s+/', ' ', $cleaned);
    return trim($cleaned);
}

function buildDefaultAmenityIcons() {
    return [
        'swimming'   => 'https://api.dpremiumhomes.com/storage/uploads/amenities/swimming_pool.png',
        'pool'       => 'https://api.dpremiumhomes.com/storage/uploads/amenities/swimming_pool.png',
        'gym'        => 'https://api.dpremiumhomes.com/storage/uploads/amenities/gym.png',
        'fitness'    => 'https://api.dpremiumhomes.com/storage/uploads/amenities/gym.png',
        'security'   => 'https://api.dpremiumhomes.com/storage/uploads/amenities/security.png',
        'cctv'       => 'https://api.dpremiumhomes.com/storage/uploads/amenities/security.png',
        'lift'       => 'https://api.dpremiumhomes.com/storage/uploads/amenities/lift.png',
        'elevator'   => 'https://api.dpremiumhomes.com/storage/uploads/amenities/lift.png',
        'play'       => 'https://api.dpremiumhomes.com/storage/uploads/amenities/kids_zone.png',
        'kids'       => 'https://api.dpremiumhomes.com/storage/uploads/amenities/kids_zone.png',
        'garden'     => 'https://api.dpremiumhomes.com/storage/uploads/amenities/garden.png',
        'rooftop'    => 'https://api.dpremiumhomes.com/storage/uploads/amenities/garden.png',
        'generator'  => 'https://api.dpremiumhomes.com/storage/uploads/amenities/generator.png',
        'electricity'=> 'https://api.dpremiumhomes.com/storage/uploads/amenities/generator.png',
        'backup'     => 'https://api.dpremiumhomes.com/storage/uploads/amenities/generator.png',
        'power'      => 'https://api.dpremiumhomes.com/storage/uploads/amenities/generator.png',
        'fire'       => 'https://api.dpremiumhomes.com/storage/uploads/amenities/fire_safety.png',
        'safety'     => 'https://api.dpremiumhomes.com/storage/uploads/amenities/fire_safety.png',
        'hall'       => 'https://api.dpremiumhomes.com/storage/uploads/amenities/community_hall.png',
        'community'  => 'https://api.dpremiumhomes.com/storage/uploads/amenities/community_hall.png',
        'parking'    => 'https://api.dpremiumhomes.com/storage/uploads/amenities/parking.png',
        'car'        => 'https://api.dpremiumhomes.com/storage/uploads/amenities/parking.png',
    ];
}

function buildAmenitiesList($p, $formatImg, $baseUrl) {
    $defaultIcons = buildDefaultAmenityIcons();
    $findIcon = function($name) use ($defaultIcons) {
        $lower = strtolower($name);
        foreach ($defaultIcons as $key => $icon) {
            if (strpos($lower, $key) !== false) {
                return $icon;
            }
        }
        return 'https://api.dpremiumhomes.com/storage/uploads/amenities/security.png';
    };

    $amenities = [];
    $idCounter = 1;

    // Check custom amenities details if set
    if (!empty($p['amenities_details'])) {
        $decoded = json_decode($p['amenities_details'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $am) {
                $amName = is_array($am) ? ($am['name'] ?? 'Amenity') : (string)$am;
                $amImg = is_array($am) && !empty($am['image']) ? $formatImg($am['image']) : $findIcon($amName);
                $amenities[] = [
                    'id'    => $idCounter++,
                    'name'  => $amName,
                    'image' => $amImg,
                ];
            }
        }
    }

    if (empty($amenities)) {
        if (!empty($p['swimming_pool'])) {
            $amenities[] = [
                'id'    => $idCounter++,
                'name'  => 'Rooftop Swimming Pool',
                'image' => $defaultIcons['swimming'],
            ];
        }
        $amenities[] = [
            'id'    => $idCounter++,
            'name'  => 'Modern Fitness Gym',
            'image' => $defaultIcons['gym'],
        ];
        $amenities[] = [
            'id'    => $idCounter++,
            'name'  => '24/7 Security & CCTV',
            'image' => $defaultIcons['security'],
        ];
        if (!empty($p['passenger_lift']) || !empty($p['cargo_lift'])) {
            $pLift = (int)($p['passenger_lift'] ?? 1);
            $cLift = (int)($p['cargo_lift'] ?? 0);
            $liftText = ($cLift > 0) ? "Passenger & Cargo Lifts ({$pLift}+{$cLift})" : "High-Speed Passenger Lift ({$pLift})";
            $amenities[] = [
                'id'    => $idCounter++,
                'name'  => $liftText,
                'image' => $defaultIcons['lift'],
            ];
        }
        if (!empty($p['rooftop_gardening'])) {
            $amenities[] = [
                'id'    => $idCounter++,
                'name'  => 'Landscaped Rooftop Garden',
                'image' => $defaultIcons['garden'],
            ];
        }
        if (!empty($p['electricity_backup'])) {
            $amenities[] = [
                'id'    => $idCounter++,
                'name'  => '100% Standby Power Backup',
                'image' => $defaultIcons['generator'],
            ];
        }
        if (!empty($p['car_parking'])) {
            $amenities[] = [
                'id'    => $idCounter++,
                'name'  => 'Secure Car Parking',
                'image' => $defaultIcons['parking'],
            ];
        }
        $amenities[] = [
            'id'    => $idCounter++,
            'name'  => 'Fire Safety & Hydrant System',
            'image' => $defaultIcons['fire'],
        ];
        if (!empty($p['convention'])) {
            $amenities[] = [
                'id'    => $idCounter++,
                'name'  => 'Community & Banquet Hall',
                'image' => $defaultIcons['hall'],
            ];
        }
    }

    return $amenities;
}

function parseProjectUnits($p, $formatImg, $mainImage) {
    $units = [];
    $idCounter = 1;

    $meta = $p['floor_wise_unit_meta'] ?? null;
    if (is_string($meta)) {
        $meta = json_decode($meta, true);
    }

    if (is_array($meta)) {
        $rawUnits = [];
        foreach ($meta as $k => $val) {
            if (is_array($val)) {
                if (isset($val['unit_name']) || isset($val['name']) || isset($val['sqft'])) {
                    $rawUnits[] = $val;
                } else {
                    foreach ($val as $subVal) {
                        if (is_array($subVal)) {
                            $rawUnits[] = $subVal;
                        }
                    }
                }
            }
        }

        foreach ($rawUnits as $u) {
            $rawName = trim($u['unit_name'] ?? $u['name'] ?? '');
            $uSqft   = $u['sqft'] ?? $u['size'] ?? 1200;
            if (is_string($uSqft)) {
                preg_match('/(\d+)/', str_replace(',', '', $uSqft), $m);
                $numSqft = isset($m[1]) ? (int)$m[1] : 1200;
            } else {
                $numSqft = (int)$uSqft;
            }

            $disp = $rawName ?: ('Unit ' . chr(65 + count($units)));

            // Beds, baths, balconies
            $beds = $u['bedrooms'] ?? null;
            if (!$beds) {
                if ($numSqft >= 2000) $beds = "4";
                elseif ($numSqft >= 1200) $beds = "3";
                elseif ($numSqft >= 800) $beds = "2";
                else $beds = "1";
            }

            $baths = $u['bathrooms'] ?? null;
            if (!$baths) {
                if ($numSqft >= 2000) $baths = "4";
                elseif ($numSqft >= 1200) $baths = "3";
                else $baths = "2";
            }

            $balc = $u['balcony'] ?? null;
            if (!$balc) {
                if ($numSqft >= 2000) $balc = "3";
                elseif ($numSqft >= 1200) $balc = "2";
                else $balc = "1";
            }

            $unitImg = !empty($u['image']) ? $formatImg($u['image']) : (!empty($u['floor_plan_image']) ? $formatImg($u['floor_plan_image']) : $mainImage);
            $unitDesc = $u['description'] ?? $u['short_description'] ?? '';

            $units[] = [
                'id'                => (int)($u['id'] ?? $idCounter++),
                'unit_name'         => (string)$disp,
                'unit_type'         => (string)($u['unit_type'] ?? $disp),
                'sqft'              => (int)$numSqft,
                'size'              => number_format($numSqft) . " sqft",
                'bedrooms'          => (string)$beds,
                'bathrooms'         => (string)$baths,
                'balcony'           => (string)$balc,
                'status'            => (string)($u['status'] ?? 'Available'),
                'price'             => (string)($u['price'] ?? ''),
                'facing'            => (string)($u['facing'] ?? 'South-East'),
                'image'             => $unitImg,
                'floor_plan_image'  => $unitImg,
                'short_description' => (string)$unitDesc,
                'description'       => (string)$unitDesc,
            ];
        }
    }

    if (empty($units)) {
        $types = ['Type A', 'Type B', 'Type C', 'Type D'];
        $sizes = [1200, 1500, 1800, 2150];
        $count = min(max((int)($p['apartment_count'] ?? 2), 2), 4);
        for ($i = 0; $i < $count; $i++) {
            $disp = $types[$i] ?? ("Type " . chr(65 + $i));
            $s    = $sizes[$i % count($sizes)];
            $units[] = [
                'id'                => $idCounter++,
                'unit_name'         => $disp,
                'unit_type'         => $disp,
                'sqft'              => $s,
                'size'              => number_format($s) . " sqft",
                'bedrooms'          => ($s >= 1800) ? "4" : (($s >= 1400) ? "3" : "2"),
                'bathrooms'         => ($s >= 1800) ? "4" : (($s >= 1400) ? "3" : "2"),
                'balcony'           => ($s >= 1800) ? "3" : "2",
                'status'            => 'Available',
                'price'             => '',
                'facing'            => 'South-East',
                'image'             => $mainImage,
                'floor_plan_image'  => $mainImage,
                'short_description' => '',
                'description'       => '',
            ];
        }
    }

    return $units;
}

// -------------------------------------------------------------
// Request Handling
// -------------------------------------------------------------
$rawInput = file_get_contents('php://input');
$jsonBody = !empty($rawInput) ? json_decode($rawInput, true) : [];

$projectId = (int)($_GET['id'] ?? $_GET['project_id'] ?? $_POST['id'] ?? $jsonBody['id'] ?? $jsonBody['project_id'] ?? 0);

if ($projectId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Valid Project ID is required (e.g. ?id=5)',
        'data'    => null,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    // 1. Fetch Project with Location
    $projectStmt = $pdo->prepare("
        SELECT 
            p.*, 
            COALESCE(l.name, '') AS location_name,
            COALESCE(c.name, '') AS community_name
        FROM projects p
        LEFT JOIN locations l ON p.location_id = l.id
        LEFT JOIN communities c ON p.community_id = c.id
        WHERE p.id = ? AND p.deleted_at IS NULL
        LIMIT 1
    ");
    $projectStmt->execute([$projectId]);
    $project = $projectStmt->fetch();

    if (!$project) {
        echo json_encode([
            'success' => false,
            'message' => "Project not found for ID {$projectId}",
            'data'    => null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // 2. Images formatting
    $mainImg = $formatImg($project['image'] ?? '');
    $img2    = $formatImg($project['image2'] ?? '');
    $img3    = $formatImg($project['image3'] ?? '');
    $mapImg  = $formatImg($project['map_image'] ?? '');

    $imagesList = [];
    if (!empty($mainImg)) $imagesList[] = $mainImg;
    if (!empty($img2))    $imagesList[] = $img2;
    if (!empty($img3))    $imagesList[] = $img3;

    // 3. Location determination
    $locationStr = !empty($project['location_name']) ? $project['location_name'] : (!empty($project['project_map_location']) ? $project['project_map_location'] : '');

    // 4. Units parsing
    $units = parseProjectUnits($project, $formatImg, $mainImg);

    // 5. Gallery Images parsing
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
                    ];
                }
            }
        }
    }

    // 6. Nearby Locations parsing
    $nearbyList = [];
    if (!empty($project['nearby_locations'])) {
        $rawNearby = json_decode($project['nearby_locations'], true);
        if (is_array($rawNearby)) {
            foreach ($rawNearby as $nItem) {
                $nImg = $formatImg($nItem['image'] ?? '');
                $nearbyList[] = [
                    'name'      => (string)($nItem['name'] ?? ''),
                    'distance'  => (string)($nItem['distance'] ?? ''),
                    'map_image' => $nImg,
                    'image'     => $nImg,
                ];
            }
        }
    }

    // 7. Amenities List
    $amenities = buildAmenitiesList($project, $formatImg, $baseUrl);

    // 8. Construction Progress
    $progressStmt = $pdo->prepare("
        SELECT 
            structural_work,
            plaster_work,
            brick_work,
            electrical_work,
            plumbing_work,
            tiles_work,
            paint_work,
            total_progress,
            last_updated_date,
            last_month_target,
            last_month_achieved
        FROM project_progress
        WHERE project_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $progressStmt->execute([$projectId]);
    $progressRow = $progressStmt->fetch();

    $formatPct = function($val) {
        $num = (float)$val;
        return (floor($num) == $num ? (int)$num : number_format($num, 1)) . '%';
    };

    if ($progressRow) {
        $totalProgressPct = $formatPct($progressRow['total_progress'] ?? 0);
        $totalProgressNum = (float)($progressRow['total_progress'] ?? 0);
        $progressLastUpdated = (string)($progressRow['last_updated_date'] ?: date('Y-m-d'));
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
        $totalProgressNum = 0.0;
        $progressLastUpdated = date('Y-m-d');
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

    // 9. Handover Date determination
    $handoverDate = !empty($project['handover']) 
        ? $project['handover'] 
        : (!empty($project['expected_handover_date']) ? $project['expected_handover_date'] : 'To be announced');

    // 10. Front Road formatting
    $frontRoad = !empty($project['front_road']) ? trim($project['front_road']) : '';
    if (!empty($frontRoad) && !preg_match('/ft|feet|m/i', $frontRoad)) {
        $frontRoadFormatted = $frontRoad . ' ft';
    } else {
        $frontRoadFormatted = $frontRoad;
    }

    // 11. Compile Final Response Payload
    $responsePayload = [
        'id'                                    => (int)$project['id'],
        'name'                                  => (string)$project['name'],
        'project_name'                          => (string)$project['name'],
        'slug'                                  => (string)($project['slug'] ?? ''),
        'location'                              => (string)$locationStr,
        'project_map_location'                  => (string)($project['project_map_location'] ?? ''),
        'price'                                 => (string)($project['price_range'] ?? ''),
        'price_range'                           => (string)($project['price_range'] ?? ''),
        'description'                           => (string)($project['description'] ?? ''),
        'short_description'                     => cleanDescription($project['description'] ?? ''),
        'images'                                => $imagesList,
        'image'                                 => $mainImg,
        'image2'                                => $img2,
        'image3'                                => $img3,
        'map_image'                             => $mapImg,
        'map_link'                              => (string)($project['map_link'] ?? ''),
        'expected_handover_date'                => (string)$handoverDate,
        'handover'                              => (string)$handoverDate,
        'unit_type'                             => (string)($project['types'] ?? 'Residential Apartments'),
        'types'                                 => (string)($project['types'] ?? ''),
        'total_share'                           => (int)($project['total_share'] ?? 0),
        'unit_per_floor'                        => (int)($project['unit_per_floor'] ?? 0),
        'front_road'                            => (string)$frontRoadFormatted,
        'passenger_lift'                        => (int)($project['passenger_lift'] ?? 0),
        'passenger_lift_number'                 => (int)($project['passenger_lift'] ?? 0),
        'cargo_lift'                            => (int)($project['cargo_lift'] ?? 0),
        'building_type'                         => (string)($project['building_type'] ?? ''),
        'plot_size'                             => !empty($project['plot_size']) ? (float)$project['plot_size'] : null,
        'apartment_count'                       => (int)($project['apartment_count'] ?? count($units)),
        'brochure_link'                         => (string)($project['brochure_link'] ?? ''),
        'project_sale_status'                   => (string)($project['project_sale_status'] ?? 'On Sale'),
        'construction_progress_percentage_total' => $totalProgressPct,
        'construction_progress_total_numeric'   => $totalProgressNum,
        'construction_progress_last_updated'    => $progressLastUpdated,
        'construction_progress_category_wise'   => $categoryWiseProgress,
        'amenities_list'                        => $amenities,
        'amenities'                             => $amenities,
        'units'                                 => $units,
        'gallery_images'                        => $galleryList,
        'nearby_locations'                      => $nearbyList,
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Project details fetched successfully',
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
