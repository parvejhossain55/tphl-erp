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
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=tphl_db;charset=utf8mb4", 'root', '', $options);
    } catch (\PDOException $e2) {
        echo json_encode(['success' => false, 'message' => 'DB Connection failed: ' . $e2->getMessage()]);
        exit;
    }
}

/**
 * Clean and strip all HTML tags from description.
 */
function cleanDescription($desc) {
    if (empty($desc)) {
        return '';
    }

    $cleaned = preg_replace('/<(?:br|p|div|li|h[1-6]|tr|td|th)\s*\/?>/i', ' ', $desc);
    $cleaned = preg_replace('/<\/(?:p|div|li|h[1-6]|tr|td|th)>/i', ' ', $cleaned);
    $cleaned = strip_tags($cleaned);
    $cleaned = html_entity_decode($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $cleaned = str_replace("\xc2\xa0", ' ', $cleaned);
    $cleaned = preg_replace('/\s+/', ' ', $cleaned);

    return trim($cleaned);
}

/**
 * Standard amenity icons mapping.
 */
function buildDefaultAmenityIcons() {
    return [
        'swimming' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/swimming_pool.png',
        'pool' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/swimming_pool.png',
        'gym' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/gym.png',
        'fitness' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/gym.png',
        'security' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/security.png',
        'cctv' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/security.png',
        'lift' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/lift.png',
        'elevator' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/lift.png',
        'play' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/kids_zone.png',
        'kids' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/kids_zone.png',
        'garden' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/garden.png',
        'rooftop' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/garden.png',
        'generator' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/generator.png',
        'electricity' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/generator.png',
        'backup' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/generator.png',
        'power' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/generator.png',
        'fire' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/fire_safety.png',
        'safety' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/fire_safety.png',
        'hall' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/community_hall.png',
        'community' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/community_hall.png',
        'banquet' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/community_hall.png',
        'convention' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/community_hall.png',
        'parking' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/parking.png',
        'car' => 'https://api.dpremiumhomes.com/storage/uploads/amenities/parking.png',
    ];
}

/**
 * Build rich amenities list with id, name, and image / img.
 */
function buildAmenitiesList($p, $formatImg) {
    $defaultIcons = buildDefaultAmenityIcons();
    $baseUrl = 'https://thepremiumhomesltd.com/TPHL-ERP';

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

    // Location specific amenities from database
    if (!empty($p['location_amenities']) && is_array($p['location_amenities'])) {
        foreach ($p['location_amenities'] as $la) {
            $amImg = !empty($la['image']) ? "{$baseUrl}/public/uploads/amenities/" . ltrim($la['image'], '/') : $findIcon($la['name']);
            $amenities[] = [
                'id' => (int)($la['id'] ?? $idCounter++),
                'name' => (string)$la['name'],
                'image' => (string)$amImg,
                'img' => (string)$amImg,
            ];
        }
    }

    if (!empty($p['amenities']) && is_array($p['amenities'])) {
        foreach ($p['amenities'] as $am) {
            if (is_array($am)) {
                $name = $am['name'] ?? 'Amenity';
                $img = !empty($am['img']) ? $formatImg($am['img']) : (!empty($am['image']) ? $formatImg($am['image']) : $findIcon($name));
                $amenities[] = [
                    'id' => (int)($am['id'] ?? $idCounter++),
                    'name' => (string)$name,
                    'image' => (string)$img,
                    'img' => (string)$img,
                ];
            } elseif (is_string($am) && trim($am) !== '') {
                $name = trim($am);
                $icon = $findIcon($name);
                $amenities[] = [
                    'id' => $idCounter++,
                    'name' => $name,
                    'image' => $icon,
                    'img' => $icon,
                ];
            }
        }
    }

    if (empty($amenities)) {
        if (!empty($p['swimming_pool'])) {
            $amenities[] = [
                'id' => $idCounter++,
                'name' => 'Rooftop Swimming Pool',
                'image' => $defaultIcons['swimming'],
                'img' => $defaultIcons['swimming'],
            ];
        }

        $amenities[] = [
            'id' => $idCounter++,
            'name' => 'Modern Fitness Gym',
            'image' => $defaultIcons['gym'],
            'img' => $defaultIcons['gym'],
        ];

        $amenities[] = [
            'id' => $idCounter++,
            'name' => '24/7 Security & CCTV',
            'image' => $defaultIcons['security'],
            'img' => $defaultIcons['security'],
        ];

        if (!empty($p['passenger_lift']) || !empty($p['cargo_lift'])) {
            $pLift = (int)($p['passenger_lift'] ?? 1);
            $cLift = (int)($p['cargo_lift'] ?? 0);
            $liftText = ($cLift > 0) ? "Passenger & Cargo Lifts ({$pLift}+{$cLift})" : "High-Speed Passenger Lift ({$pLift})";
            $amenities[] = [
                'id' => $idCounter++,
                'name' => $liftText,
                'image' => $defaultIcons['lift'],
                'img' => $defaultIcons['lift'],
            ];
        } else {
            $amenities[] = [
                'id' => $idCounter++,
                'name' => 'High-Speed Passenger Lift',
                'image' => $defaultIcons['lift'],
                'img' => $defaultIcons['lift'],
            ];
        }

        $amenities[] = [
            'id' => $idCounter++,
            'name' => 'Kids Play Zone',
            'image' => $defaultIcons['play'],
            'img' => $defaultIcons['play'],
        ];

        if (!empty($p['rooftop_gardening'])) {
            $amenities[] = [
                'id' => $idCounter++,
                'name' => 'Landscaped Rooftop Garden',
                'image' => $defaultIcons['garden'],
                'img' => $defaultIcons['garden'],
            ];
        }

        if (!empty($p['electricity_backup'])) {
            $amenities[] = [
                'id' => $idCounter++,
                'name' => '100% Standby Power Backup',
                'image' => $defaultIcons['generator'],
                'img' => $defaultIcons['generator'],
            ];
        }

        if (!empty($p['car_parking'])) {
            $amenities[] = [
                'id' => $idCounter++,
                'name' => 'Car Parking',
                'image' => $defaultIcons['parking'],
                'img' => $defaultIcons['parking'],
            ];
        }

        $amenities[] = [
            'id' => $idCounter++,
            'name' => 'Fire Fighting & Smoke Detectors',
            'image' => $defaultIcons['fire'],
            'img' => $defaultIcons['fire'],
        ];

        if (!empty($p['convention'])) {
            $amenities[] = [
                'id' => $idCounter++,
                'name' => 'Community & Banquet Hall',
                'image' => $defaultIcons['convention'],
                'img' => $defaultIcons['convention'],
            ];
        }
    }

    return $amenities;
}

/**
 * Build single unit structure.
 */
function buildSingleUnit($u, $id, $displayName, $numSqft, $formatImg, $mainImage) {
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

    $balcony = $u['balcony'] ?? null;
    if (!$balcony) {
        if ($numSqft >= 2000) $balc = "3";
        elseif ($numSqft >= 1200) $balc = "2";
        else $balc = "1";
    } else {
        $balc = $balcony;
    }

    $planImg = !empty($u['floor_plan_image']) ? $formatImg($u['floor_plan_image']) : $mainImage;

    return [
        'id' => (int)($u['id'] ?? $id),
        'name' => (string)$displayName,
        'unit_name' => (string)($u['unit_name'] ?? $displayName),
        'flatNo' => (string)$displayName,
        'size' => number_format($numSqft) . " sqft",
        'sqft' => (int)$numSqft,
        'bedrooms' => (string)$beds,
        'bathrooms' => (string)$baths,
        'balcony' => (string)$balc,
        'status' => (string)($u['status'] ?? 'Available'),
        'price' => (string)($u['price'] ?? ''),
        'facing' => (string)($u['facing'] ?? 'South-East'),
        'floor_plan_image' => $planImg,
    ];
}

/**
 * Parse and expand all units from floor_wise_unit_meta.
 */
function parseAllUnits($p, $formatImg, $mainImage) {
    $units = [];
    $idCounter = 101;

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
            $uSqft = $u['sqft'] ?? $u['size'] ?? 1200;
            if (is_string($uSqft)) {
                preg_match('/(\d+)/', str_replace(',', '', $uSqft), $m);
                $numSqft = isset($m[1]) ? (int)$m[1] : 1200;
            } else {
                $numSqft = (int)$uSqft;
            }

            $cleaned = trim($rawName, "() ,");
            $subNames = preg_split('/[\s,&+]+/', $cleaned);
            if (count($subNames) > 1 && !preg_match('/^unit/i', $rawName)) {
                foreach ($subNames as $sn) {
                    $sn = trim($sn);
                    if ($sn === '') continue;
                    $disp = (stripos($sn, 'unit') !== false || stripos($sn, 'type') !== false) ? $sn : "Type {$sn}";
                    $units[] = buildSingleUnit($u, $idCounter++, $disp, $numSqft, $formatImg, $mainImage);
                }
            } else {
                $disp = $rawName ?: ('Unit ' . chr(65 + count($units)));
                if (!preg_match('/^(Unit|Type)\s+/i', $disp)) {
                    $disp = "Type {$disp}";
                }
                $units[] = buildSingleUnit($u, $idCounter++, $disp, $numSqft, $formatImg, $mainImage);
            }
        }
    }

    if (empty($units)) {
        $types = ['Type A', 'Type B', 'Type C', 'Type D'];
        $sizes = [1200, 1500, 1800, 2150];
        $count = min(max((int)($p['apartment_count'] ?? 2), 2), 4);
        for ($i = 0; $i < $count; $i++) {
            $disp = $types[$i] ?? ("Type " . chr(65 + $i));
            $s = $sizes[$i % count($sizes)];
            $units[] = buildSingleUnit([], $idCounter++, $disp, $s, $formatImg, $mainImage);
        }
    }

    return $units;
}

/**
 * Build specifications array.
 */
function buildSpecifications($p, $units) {
    $specs = [];

    $bType = !empty($p['building_type']) ? $p['building_type'] : 'G+9';
    $specs[] = [
        'label' => 'Towers',
        'value' => (string)$bType,
    ];

    $totalShare = !empty($p['total_share']) ? $p['total_share'] : (!empty($p['apartment_count']) ? $p['apartment_count'] : count($units));
    $specs[] = [
        'label' => 'Total Share',
        'value' => (string)$totalShare,
    ];

    $landArea = '';
    if (!empty($p['plot_size'])) {
        $landArea = $p['plot_size'] . ' Katha';
    } elseif (!empty($p['front_road'])) {
        $landArea = $p['front_road'] . ' Front Road';
    } else {
        $landArea = '10 Katha';
    }
    $specs[] = [
        'label' => 'Land Area',
        'value' => (string)$landArea,
    ];

    $sqfts = [];
    foreach ($units as $u) {
        if (!empty($u['sqft'])) {
            $sqfts[] = (int)$u['sqft'];
        }
    }
    if (!empty($sqfts)) {
        $min = min($sqfts);
        $max = max($sqfts);
        if ($min === $max) {
            $sizeVal = "{$min} SFT";
        } else {
            $sizeVal = "{$min} - {$max} SFT";
        }
    } else {
        $sizeVal = !empty($p['size']) ? $p['size'] : '1200 - 1800 SFT';
    }
    $specs[] = [
        'label' => 'Apartment Size',
        'value' => (string)$sizeVal,
    ];

    return $specs;
}

/**
 * Format project detail for /projects/{slug} with EXACT requested structure:
 * id, name, slug, description, image, image2, image3, mapImage, fullLocation, mapLink, brochureLink, apartmentCount, specifications, amenities, units
 */
function formatProjectSlug($p) {
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

    $images = [];
    if (!empty($p['images']) && is_array($p['images'])) {
        foreach ($p['images'] as $img) {
            if (is_string($img) && !empty($img)) {
                $url = $formatImg($img);
                if ($url && !in_array($url, $images)) $images[] = $url;
            } elseif (is_array($img) && !empty($img['url'])) {
                $url = $formatImg($img['url']);
                if ($url && !in_array($url, $images)) $images[] = $url;
            }
        }
    }
    foreach (['image', 'image2', 'image3', 'map_image'] as $imgKey) {
        if (!empty($p[$imgKey]) && is_string($p[$imgKey])) {
            $url = $formatImg($p[$imgKey]);
            if ($url && !in_array($url, $images)) $images[] = $url;
        }
    }

    $mainImage = !empty($images) ? $images[0] : ($formatImg($p['image'] ?? null) ?? '');
    if (empty($images) && !empty($mainImage)) $images[] = $mainImage;

    $image2 = !empty($p['image2']) ? $formatImg($p['image2']) : (isset($images[1]) ? $images[1] : $mainImage);
    $image3 = !empty($p['image3']) ? $formatImg($p['image3']) : (isset($images[2]) ? $images[2] : $image2);
    $mapImage = !empty($p['map_image']) ? $formatImg($p['map_image']) : (isset($images[3]) ? $images[3] : $mainImage);

    $units = parseAllUnits($p, $formatImg, $mainImage);
    $amenities = buildAmenitiesList($p, $formatImg);
    $specifications = buildSpecifications($p, $units);

    $buildingType = $p['building_type'] ?? '';
    $brochure = !empty($p['brochure_link']) ? (strpos($p['brochure_link'], 'http') === 0 ? $p['brochure_link'] : "{$baseUrl}/public/uploads/brochures/" . ltrim($p['brochure_link'], '/')) : '';
    $mapLink = !empty($p['map_link']) ? $p['map_link'] : (!empty($p['project_map_location']) ? $p['project_map_location'] : 'https://maps.google.com/?q=23.8759,90.3795');

    $floorPlans = [];
    foreach ($units as $u) {
        $floorPlans[] = [
            'title' => $u['name'],
            'image' => $u['floor_plan_image'] ?: $mainImage,
        ];
    }

    $beds = (int)($p['beds'] ?? ($units[0]['bedrooms'] ?? 3));
    $size = (string)($units[0]['size'] ?? '1565 sqft');
    $type = (string)($p['type'] ?? $p['types'] ?? 'Single Project');
    $price = (string)($p['price'] ?? $p['price_range'] ?? '1 Crore - 2 Crore BDT');
    $status = (string)($p['status'] ?? $p['project_sale_status'] ?? 'Ongoing');

    $data = [
        'id' => (int)($p['id'] ?? 0),
        'name' => (string)($p['name'] ?? ''),
        'slug' => (string)($p['slug'] ?? ''),
        'description' => cleanDescription($p['description'] ?? ''),
        'image' => $mainImage,
        'image2' => $image2,
        'image3' => $image3,
        'mapImage' => $mapImage,
        'fullLocation' => $locationStr,
        'mapLink' => $mapLink,
        'brochureLink' => $brochure,
        'apartmentCount' => (string)($p['apartment_count'] ?? count($units)),
        'specifications' => $specifications,
        'amenities' => $amenities,
        'units' => $units,
        // Compatibility fields
        'location' => $locationStr,
        'beds' => $beds,
        'size' => $size,
        'type' => $type,
        'price' => $price,
        'status' => $status,
        'images' => array_values($images),
        'floorPlans' => array_values($floorPlans),
    ];

    return array_merge([
        'success' => true,
        'project' => $data,
    ], $data);
}

/**
 * Format property detail for /get-property-by-id?id={} with EXACT requested structure:
 */
function formatPropertyDetailById($p) {
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

    $images = [];
    if (!empty($p['images']) && is_array($p['images'])) {
        foreach ($p['images'] as $img) {
            if (is_string($img) && !empty($img)) {
                $url = $formatImg($img);
                if ($url && !in_array($url, $images)) $images[] = $url;
            } elseif (is_array($img) && !empty($img['url'])) {
                $url = $formatImg($img['url']);
                if ($url && !in_array($url, $images)) $images[] = $url;
            }
        }
    }
    foreach (['image', 'image2', 'image3', 'map_image'] as $imgKey) {
        if (!empty($p[$imgKey]) && is_string($p[$imgKey])) {
            $url = $formatImg($p[$imgKey]);
            if ($url && !in_array($url, $images)) $images[] = $url;
        }
    }
    $mainImage = !empty($images) ? $images[0] : ($formatImg($p['image'] ?? null) ?? '');
    if (empty($images) && !empty($mainImage)) $images[] = $mainImage;

    $units = parseAllUnits($p, $formatImg, $mainImage);
    $amenities = buildAmenitiesList($p, $formatImg);

    // Location object
    $locId = (int)($p['location_id'] ?? (is_array($p['location'] ?? null) ? ($p['location']['id'] ?? 1) : 1));
    $locName = $locationStr ?: 'Bashundhara Residential Area, Dhaka';
    $locAddress = !empty($p['location']['address']) ? $p['location']['address'] : ($p['project_map_location'] ?? $locName);
    $locSlug = !empty($p['location']['slug']) ? $p['location']['slug'] : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $locName), '-'));

    $locationObj = [
        'id' => $locId,
        'name' => $locName,
        'address' => $locAddress,
        'slug' => $locSlug,
    ];

    // Community object
    $comId = (int)($p['community_id'] ?? (is_array($p['community'] ?? null) ? ($p['community']['id'] ?? 1) : 1));
    $comName = is_array($p['community'] ?? null) ? ($p['community']['name'] ?? '') : ($p['community'] ?? '');
    if (empty($comName)) $comName = ($p['name'] ?? 'Premium') . ' Community';
    $comSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $comName), '-'));

    $communityObj = [
        'id' => $comId,
        'name' => $comName,
        'slug' => $comSlug,
    ];

    $mapImage = !empty($p['map_image']) ? $formatImg($p['map_image']) : (isset($images[1]) ? $images[1] : $mainImage);
    $brochure = !empty($p['brochure_link']) ? (strpos($p['brochure_link'], 'http') === 0 ? $p['brochure_link'] : "{$baseUrl}/public/uploads/brochures/" . ltrim($p['brochure_link'], '/')) : '';

    $price = $p['price_range'] ?? $p['price'] ?? '50 Lac - 1.2 Crore BDT';
    $types = $p['types'] ?? $p['type'] ?? 'Single Project / Luxury Residential';
    $saleStatus = $p['project_sale_status'] ?? $p['status'] ?? 'On Sale';

    $property = [
        'id' => (int)($p['id'] ?? 0),
        'name' => (string)($p['name'] ?? ''),
        'slug' => (string)($p['slug'] ?? ''),
        'types' => (string)$types,
        'project_sale_status' => (string)$saleStatus,
        'price_range' => (string)$price,
        'description' => cleanDescription($p['description'] ?? ''),
        'location' => $locationObj,
        'community' => $communityObj,
        'images' => array_values($images),
        'building_type' => (string)($p['building_type'] ?? 'G+9'),
        'plot_size' => (float)($p['plot_size'] ?? 5.0),
        'block_no' => (string)($p['block_no'] ?? 'Block-C'),
        'front_road' => (string)($p['front_road'] ?? '40 Feet'),
        'total_share' => (int)($p['total_share'] ?? count($units)),
        'apartment_count' => (int)($p['apartment_count'] ?? count($units)),
        'unit_per_floor' => (int)($p['unit_per_floor'] ?? count($units)),
        'passenger_lift' => (int)($p['passenger_lift'] ?? 1),
        'cargo_lift' => (int)($p['cargo_lift'] ?? 0),
        'car_parking' => (int)($p['car_parking'] ?? 1),
        'rooftop_gardening' => (int)($p['rooftop_gardening'] ?? 1),
        'electricity_backup' => (int)($p['electricity_backup'] ?? 1),
        'convention' => (int)($p['convention'] ?? 0),
        'service_charge_per_emi' => (string)($p['service_charge_per_emi'] ?? '5,000'),
        'amenities' => array_values($amenities),
        'floor_wise_unit_meta' => array_values($units),
        'units' => array_values($units),
        'project_map_location' => (string)($p['project_map_location'] ?? 'https://maps.google.com/?q=23.8759,90.3795'),
        'map_link' => (string)($p['map_link'] ?? $p['project_map_location'] ?? 'https://maps.google.com/?q=23.8759,90.3795'),
        'map_image' => (string)$mapImage,
        'latitude' => (string)($p['latitude'] ?? '23.8759'),
        'longitude' => (string)($p['longitude'] ?? '90.3795'),
        'brochure_link' => (string)$brochure,
        'video_tour_url' => (string)($p['video_tour_url'] ?? ''),
        'contact_phone' => (string)($p['contact_phone'] ?? '+8801700000000'),
        'contact_email' => (string)($p['contact_email'] ?? 'sales@thepremiumhomesltd.com'),
        'created_at' => !empty($p['created_at']) ? date('c', strtotime($p['created_at'])) : date('c'),
        'updated_at' => !empty($p['updated_at']) ? date('c', strtotime($p['updated_at'])) : date('c'),
        // Compatibility fields
        'beds' => (int)($p['beds'] ?? ($units[0]['bedrooms'] ?? 3)),
        'size' => (string)($units[0]['size'] ?? '1565 sqft'),
        'type' => (string)$types,
        'price' => (string)$price,
    ];

    return [
        'success' => true,
        'message' => 'Property loaded successfully.',
        'property' => $property,
    ];
}

/**
 * Format project for list view.
 */
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
        'description' => cleanDescription($p['description'] ?? ''),
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

            if (!empty($p['location_id'])) {
                try {
                    $amStmt = $pdo->prepare("SELECT id, name, image FROM amenities WHERE location_id = ?");
                    $amStmt->execute([$p['location_id']]);
                    $locAmenities = $amStmt->fetchAll();
                    if ($locAmenities) {
                        $p['location_amenities'] = $locAmenities;
                    }
                } catch (Exception $e) {}
            }

            echo json_encode(formatProjectSlug($p));
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

            if (!empty($p['location_id'])) {
                try {
                    $amStmt = $pdo->prepare("SELECT id, name, image FROM amenities WHERE location_id = ?");
                    $amStmt->execute([$p['location_id']]);
                    $locAmenities = $amStmt->fetchAll();
                    if ($locAmenities) {
                        $p['location_amenities'] = $locAmenities;
                    }
                } catch (Exception $e) {}
            }

            echo json_encode(formatPropertyDetailById($p));
        } else {
            echo json_encode(['success' => false, 'message' => 'Not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
