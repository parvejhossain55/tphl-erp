<?php
// Simple API script to serve projects, bypassing the need for Remote MySQL.
// Upload this to the root of TPHL-ERP folder on the KVM 8 server.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database credentials for KVM 8
$host = '127.0.0.1';
$db   = 'final_erp_db';
$user = 'final_erp_user';
$pass = 'finaltPhl@#5672dhKL%sd10';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB Connection failed: ' . $e->getMessage()]);
    exit;
}

/**
 * Format project detail for /projects/{slug} and /get-property-by-id with EXACT structure:
 * id, name, slug, description, location, beds, size, type, price, images, floorPlans, amenities, units
 */
function formatProjectDetail($p) {
    if (!is_array($p)) return $p;

    $baseUrl = 'https://thepremiumhomesltd.com/TPHL-ERP';

    $formatImg = function($img) use ($baseUrl) {
        if (empty($img)) return null;
        if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) return $img;
        return $baseUrl . '/public/uploads/projects/' . ltrim($img, '/');
    };

    $locationStr = '';
    if (isset($p['location'])) {
        if (is_array($p['location'])) {
            $locationStr = $p['location']['name'] ?? '';
        } elseif (is_string($p['location'])) {
            $locationStr = $p['location'];
        }
    }
    if (empty($locationStr) && !empty($p['project_map_location'])) {
        $locationStr = $p['project_map_location'];
    }

    // Extract / preserve all images
    $images = [];
    if (!empty($p['images']) && is_array($p['images'])) {
        foreach ($p['images'] as $img) {
            if (is_string($img) && !empty($img)) {
                $url = $formatImg($img);
                if ($url && !in_array($url, $images)) {
                    $images[] = $url;
                }
            } elseif (is_array($img) && !empty($img['url'])) {
                $url = $formatImg($img['url']);
                if ($url && !in_array($url, $images)) {
                    $images[] = $url;
                }
            }
        }
    }

    foreach (['image', 'image2', 'image3', 'map_image', 'amenities_image'] as $imgKey) {
        if (!empty($p[$imgKey]) && is_string($p[$imgKey])) {
            $url = $formatImg($p[$imgKey]);
            if ($url && !in_array($url, $images)) {
                $images[] = $url;
            }
        }
    }

    $mainImage = !empty($images) ? $images[0] : ($formatImg($p['image'] ?? null) ?? '');
    if (empty($images) && !empty($mainImage)) {
        $images[] = $mainImage;
    }

    $firstSqft = null;
    $units = [];
    $floorPlans = [];

    // If floorPlans already provided
    if (!empty($p['floorPlans']) && is_array($p['floorPlans'])) {
        foreach ($p['floorPlans'] as $fp) {
            if (is_array($fp)) {
                $fpTitle = $fp['title'] ?? 'Type A';
                $fpImg = !empty($fp['image']) ? $formatImg($fp['image']) : $mainImage;
                $floorPlans[] = [
                    'title' => $fpTitle,
                    'image' => $fpImg ?: $mainImage
                ];
            }
        }
    }

    if (!empty($p['floor_wise_unit_meta'])) {
        $meta = is_string($p['floor_wise_unit_meta']) 
            ? json_decode($p['floor_wise_unit_meta'], true) 
            : $p['floor_wise_unit_meta'];

        if (is_array($meta)) {
            $letter = 'A';
            foreach ($meta as $floorType => $floorUnits) {
                if (is_array($floorUnits)) {
                    foreach ($floorUnits as $u) {
                        $uName = $u['unit_name'] ?? $u['name'] ?? ('Unit ' . $letter++);
                        $uSqft = $u['sqft'] ?? $u['size'] ?? null;
                        if ($uSqft && !$firstSqft) {
                            $firstSqft = $uSqft;
                        }
                        $sizeText = $uSqft ? ($uSqft . ' sft') : '';

                        $units[] = [
                            'flatNo' => $uName,
                            'size' => $sizeText ?: ($p['size'] ?? '1565 sft'),
                            'status' => 'Available'
                        ];

                        if (empty($p['floorPlans'])) {
                            $planImg = (count($images) > 1 && count($floorPlans) === 0) 
                                ? $images[1] 
                                : (!empty($p['image2']) ? $formatImg($p['image2']) : $mainImage);

                            $floorPlans[] = [
                                'title' => (stripos($uName, 'unit') !== false ? preg_replace('/unit/i', 'Type', $uName) : "Type " . $uName),
                                'image' => $planImg ?: $mainImage
                            ];
                        }
                    }
                }
            }
        }
    }

    // If units already provided
    if (!empty($p['units']) && is_array($p['units']) && empty($units)) {
        foreach ($p['units'] as $u) {
            if (is_array($u)) {
                $units[] = [
                    'flatNo' => $u['flatNo'] ?? $u['flat_no'] ?? $u['unit_name'] ?? '1A',
                    'size' => $u['size'] ?? ($p['size'] ?? '1565 sft'),
                    'status' => $u['status'] ?? 'Available'
                ];
            }
        }
    }

    if (empty($units)) {
        $aptCount = (int)($p['apartment_count'] ?? 1);
        for ($i = 1; $i <= min($aptCount, 5); $i++) {
            $units[] = [
                'flatNo' => "{$i}A",
                'size' => $p['size'] ?? ($firstSqft ? ($firstSqft . ' sft') : '1565 sft'),
                'status' => 'Available'
            ];
        }
    }

    if (empty($floorPlans)) {
        $planImg = (count($images) > 1) ? $images[1] : (!empty($p['image2']) ? $formatImg($p['image2']) : $mainImage);
        $floorPlans[] = [
            'title' => 'Type A',
            'image' => $planImg ?: $mainImage
        ];
    }

    $amenities = [];
    if (!empty($p['amenities']) && is_array($p['amenities'])) {
        $amenities = array_values(array_filter($p['amenities']));
    } else {
        if (!empty($p['swimming_pool'])) $amenities[] = "Swimming Pool";
        if (!empty($p['rooftop_gardening'])) $amenities[] = "Rooftop Gardening";
        if (!empty($p['convention'])) $amenities[] = "Convention Hall";
        if (!empty($p['electricity_backup'])) $amenities[] = "Electricity Backup";
        if (!empty($p['car_parking'])) $amenities[] = "Car Parking";
        if (!empty($p['passenger_lift'])) $amenities[] = "Passenger Lift (" . $p['passenger_lift'] . ")";
        if (!empty($p['cargo_lift'])) $amenities[] = "Cargo Lift (" . $p['cargo_lift'] . ")";

        if (empty($amenities) && !empty($p['amenities_details'])) {
            $amenities = array_filter(array_map('trim', explode(',', $p['amenities_details'])));
        }
        if (empty($amenities)) {
            $amenities = ["Modern Security", "Electricity Backup", "Car Parking"];
        }
    }

    $beds = $p['beds'] ?? null;
    if ($beds === null) {
        if (!empty($p['description']) && preg_match('/(\d+)\s*(?:bed|bedroom|bhk)/i', $p['description'], $matches)) {
            $beds = (int)$matches[1];
        } elseif (!empty($p['hover_text']) && preg_match('/(\d+)\s*(?:bed|bedroom|bhk)/i', $p['hover_text'], $matches)) {
            $beds = (int)$matches[1];
        } else {
            $beds = (int)($p['unit_per_floor'] ?? $p['apartment_count'] ?? 2);
            if ($beds <= 0) $beds = 2;
        }
    }

    $size = $p['size'] ?? null;
    if (empty($size)) {
        if ($firstSqft) {
            $size = $firstSqft . ' sft';
        } elseif (!empty($p['plot_size'])) {
            $size = $p['plot_size'] . ' Katha';
        } else {
            $size = '1565 sft';
        }
    }

    $type = $p['type'] ?? $p['types'] ?? $p['building_type'] ?? 'Pre Construction';
    $price = $p['price'] ?? $p['price_range'] ?? '42.00 Lakh';

    return [
        'id' => (int)($p['id'] ?? 0),
        'name' => (string)($p['name'] ?? ''),
        'slug' => (string)($p['slug'] ?? ''),
        'description' => (string)($p['description'] ?? ''),
        'location' => (string)$locationStr,
        'beds' => (int)$beds,
        'size' => (string)$size,
        'type' => (string)$type,
        'price' => (string)$price,
        'images' => array_values($images),
        'floorPlans' => array_values($floorPlans),
        'amenities' => array_values($amenities),
        'units' => array_values($units),
    ];
}

function formatProjectData($p) {
    if (!is_array($p)) return $p;

    $baseUrl = 'https://thepremiumhomesltd.com/TPHL-ERP';

    $formatImg = function($img) use ($baseUrl) {
        if (empty($img)) return null;
        if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) return $img;
        return $baseUrl . '/public/uploads/projects/' . ltrim($img, '/');
    };

    $locationStr = '';
    if (isset($p['location'])) {
        if (is_array($p['location'])) {
            $locationStr = $p['location']['name'] ?? '';
        } elseif (is_string($p['location'])) {
            $locationStr = $p['location'];
        }
    }
    if (empty($locationStr) && !empty($p['project_map_location'])) {
        $locationStr = $p['project_map_location'];
    }

    $mainImage = $formatImg($p['image'] ?? null) 
        ?: ($formatImg($p['image2'] ?? null) 
        ?: ($formatImg($p['image3'] ?? null) ?: ''));

    $firstSqft = null;
    if (!empty($p['floor_wise_unit_meta'])) {
        $meta = is_string($p['floor_wise_unit_meta']) 
            ? json_decode($p['floor_wise_unit_meta'], true) 
            : $p['floor_wise_unit_meta'];

        if (is_array($meta)) {
            foreach ($meta as $floorType => $floorUnits) {
                if (is_array($floorUnits)) {
                    foreach ($floorUnits as $u) {
                        $uSqft = $u['sqft'] ?? $u['size'] ?? null;
                        if ($uSqft && !$firstSqft) {
                            $firstSqft = $uSqft;
                            break 2;
                        }
                    }
                }
            }
        }
    }

    $beds = $p['beds'] ?? null;
    if ($beds === null) {
        $beds = (int)($p['unit_per_floor'] ?? $p['apartment_count'] ?? 2);
        if ($beds <= 0) $beds = 2;
    }

    $size = $p['size'] ?? null;
    if (empty($size)) {
        if ($firstSqft) {
            $size = $firstSqft . ' sft';
        } elseif (!empty($p['plot_size'])) {
            $size = $p['plot_size'] . ' Katha';
        } else {
            $size = '1565 sft';
        }
    }

    $type = $p['type'] ?? $p['types'] ?? $p['building_type'] ?? 'Pre Construction';
    $price = $p['price'] ?? $p['price_range'] ?? '42.00 Lakh';
    $status = $p['status'] ?? $p['project_sale_status'] ?? $p['project_status'] ?? 'Ongoing';
    if (empty($status) || $status === 'On Sale') {
        $status = 'Ongoing';
    }

    $formattedFields = [
        'id' => (int)($p['id'] ?? 0),
        'name' => $p['name'] ?? '',
        'slug' => $p['slug'] ?? '',
        'location' => $locationStr,
        'image' => $mainImage,
        'beds' => (int)$beds,
        'size' => (string)$size,
        'type' => (string)$type,
        'price' => (string)$price,
        'status' => (string)$status,
    ];

    return array_merge($p, $formattedFields);
}

$action = $_GET['action'] ?? 'all';

if ($action === 'all') {
    $sql = "
        SELECT p.*, l.name as location_name, c.name as community_name 
        FROM projects p 
        LEFT JOIN locations l ON p.location_id = l.id 
        LEFT JOIN communities c ON p.community_id = c.id 
        WHERE p.deleted_at IS NULL
    ";
    
    try {
        $stmt = $pdo->query($sql);
        $projects = [];
        while ($p = $stmt->fetch()) {
            $locName = $p['location_name'];
            $comName = $p['community_name'];
            unset($p['location_name'], $p['community_name']);
            
            $p['location'] = ['name' => $locName];
            $p['community'] = ['name' => $comName];
            
            if (isset($p['floor_wise_unit_meta']) && is_string($p['floor_wise_unit_meta'])) {
                $p['floor_wise_unit_meta'] = json_decode($p['floor_wise_unit_meta'], true);
            }
            $projects[] = formatProjectData($p);
        }
        echo json_encode(['success' => true, 'projects' => $projects]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'find-by-slug') {
    $slug = $_GET['slug'] ?? '';
    $sql = "
        SELECT p.*, l.name as location_name, c.name as community_name 
        FROM projects p 
        LEFT JOIN locations l ON p.location_id = l.id 
        LEFT JOIN communities c ON p.community_id = c.id 
        WHERE p.slug = ? AND p.deleted_at IS NULL
        LIMIT 1
    ";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$slug]);
        $p = $stmt->fetch();
        if ($p) {
            $locName = $p['location_name'];
            $comName = $p['community_name'];
            unset($p['location_name'], $p['community_name']);
            
            $p['location'] = ['name' => $locName];
            $p['community'] = ['name' => $comName];
            
            if (isset($p['floor_wise_unit_meta']) && is_string($p['floor_wise_unit_meta'])) {
                $p['floor_wise_unit_meta'] = json_decode($p['floor_wise_unit_meta'], true);
            }
            echo json_encode([
                'success' => true,
                'project' => formatProjectDetail($p)
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'find') {
    $id = $_GET['id'] ?? 0;
    $sql = "
        SELECT p.*, l.name as location_name, c.name as community_name 
        FROM projects p 
        LEFT JOIN locations l ON p.location_id = l.id 
        LEFT JOIN communities c ON p.community_id = c.id 
        WHERE p.id = ? AND p.deleted_at IS NULL
        LIMIT 1
    ";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if ($p) {
            $locName = $p['location_name'];
            $comName = $p['community_name'];
            unset($p['location_name'], $p['community_name']);
            
            $p['location'] = ['name' => $locName];
            $p['community'] = ['name' => $comName];
            
            if (isset($p['floor_wise_unit_meta']) && is_string($p['floor_wise_unit_meta'])) {
                $p['floor_wise_unit_meta'] = json_decode($p['floor_wise_unit_meta'], true);
            }
            echo json_encode([
                'success' => true,
                'message' => 'Property loaded successfully.',
                'property' => formatProjectDetail($p)
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
