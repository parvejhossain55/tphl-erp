<?php
// API endpoint to fetch General Projects List
// Returns: Project Name, Location, Image link, Price, Expected Handover date, Total completion Percentage

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
            echo json_encode(['success' => false, 'message' => 'DB Connection failed: ' . $e2->getMessage(), 'data' => []]);
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
            echo json_encode(['success' => false, 'message' => 'DB Connection failed: ' . $e2->getMessage(), 'data' => []]);
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

$formatPct = function($val) {
    $num = (float)$val;
    return (floor($num) == $num ? (int)$num : number_format($num, 1)) . '%';
};

$formatRangeOrVal = function(array $values, $unitSuffix = '') {
    $vals = array_values(array_filter(array_unique($values), function($v) { return $v > 0; }));
    if (empty($vals)) {
        return '';
    }
    sort($vals, SORT_NUMERIC);
    $str = count($vals) === 1 ? (string)$vals[0] : (min($vals) . ' - ' . max($vals));
    return $unitSuffix !== '' ? ($str . ' ' . $unitSuffix) : $str;
};

try {
    $whereClauses = ["p.deleted_at IS NULL"];
    $params       = [];

    // 1. Search Filter (by Project Name, Slug, Description, Location, or Community)
    $search = trim($_GET['search'] ?? $_GET['keyword'] ?? $_GET['q'] ?? '');
    if (!empty($search)) {
        $whereClauses[] = "(p.name LIKE ? OR p.slug LIKE ? OR l.name LIKE ? OR c.name LIKE ? OR p.project_map_location LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // 2. Location Filter (by ID or Location Name)
    $location = trim($_GET['location'] ?? $_GET['location_id'] ?? '');
    if (!empty($location)) {
        if (is_numeric($location)) {
            $whereClauses[] = "p.location_id = ?";
            $params[] = (int)$location;
        } else {
            $whereClauses[] = "(l.name LIKE ? OR p.project_map_location LIKE ?)";
            $params[] = "%{$location}%";
            $params[] = "%{$location}%";
        }
    }

    // 3. Community Filter (by ID or Community Name)
    $community = trim($_GET['community'] ?? $_GET['community_id'] ?? $_GET['community_name'] ?? '');
    if (!empty($community)) {
        if (is_numeric($community)) {
            $whereClauses[] = "p.community_id = ?";
            $params[] = (int)$community;
        } else {
            $whereClauses[] = "c.name LIKE ?";
            $params[] = "%{$community}%";
        }
    }

    // 4. Price Filter (by Exact match or partial match on price_range)
    $price = trim($_GET['price'] ?? $_GET['price_range'] ?? '');
    if (!empty($price)) {
        $whereClauses[] = "p.price_range LIKE ?";
        $params[] = "%{$price}%";
    }

    // 5. Sale Status Filter (Optional e.g. "On Sale", "Sold Out")
    $status = trim($_GET['status'] ?? $_GET['sale_status'] ?? '');
    if (!empty($status)) {
        $whereClauses[] = "p.project_sale_status = ?";
        $params[] = $status;
    }

    // Unit-level filter parameters
    $filterBedRaw  = $_GET['bedroom'] ?? $_GET['bedrooms'] ?? $_GET['beds'] ?? $_GET['number_of_bedroom'] ?? $_GET['number_of_bedrooms'] ?? null;
    $filterBed     = ($filterBedRaw !== null && trim((string)$filterBedRaw) !== '') ? (int)trim((string)$filterBedRaw) : null;

    $filterBathRaw = $_GET['bathroom'] ?? $_GET['bathrooms'] ?? $_GET['baths'] ?? $_GET['number_of_bathroom'] ?? $_GET['number_of_bathrooms'] ?? null;
    $filterBath    = ($filterBathRaw !== null && trim((string)$filterBathRaw) !== '') ? (int)trim((string)$filterBathRaw) : null;

    $filterBalcRaw = $_GET['balcony'] ?? $_GET['balconies'] ?? $_GET['bellcony'] ?? $_GET['bellconey'] ?? $_GET['number_of_balcony'] ?? null;
    $filterBalc    = ($filterBalcRaw !== null && trim((string)$filterBalcRaw) !== '') ? (int)trim((string)$filterBalcRaw) : null;

    $flatSizeRaw   = trim($_GET['flat_size'] ?? $_GET['size'] ?? $_GET['sqft'] ?? '');
    $minSize       = isset($_GET['min_size']) && $_GET['min_size'] !== '' ? (float)$_GET['min_size'] : (isset($_GET['min_sqft']) && $_GET['min_sqft'] !== '' ? (float)$_GET['min_sqft'] : null);
    $maxSize       = isset($_GET['max_size']) && $_GET['max_size'] !== '' ? (float)$_GET['max_size'] : (isset($_GET['max_sqft']) && $_GET['max_sqft'] !== '' ? (float)$_GET['max_sqft'] : null);

    if (!empty($flatSizeRaw)) {
        if (preg_match('/^(\d+)\s*[-to]+\s*(\d+)$/i', $flatSizeRaw, $matches)) {
            $minSize = (float)$matches[1];
            $maxSize = (float)$matches[2];
        } elseif (is_numeric($flatSizeRaw)) {
            $targetSize = (float)$flatSizeRaw;
            if ($minSize === null && $maxSize === null) {
                $minSize = $targetSize * 0.9;
                $maxSize = $targetSize * 1.1;
            }
        }
    }

    $whereSql = implode(' AND ', $whereClauses);

    $sql = "
        SELECT 
            p.*,
            COALESCE(l.name, '') AS location_name,
            COALESCE(c.name, '') AS community_name,
            COALESCE(pp.total_progress, 0.00) AS latest_total_progress,
            fl.flat_beds,
            fl.flat_baths,
            fl.flat_balcs,
            fl.flat_sizes
        FROM projects p
        LEFT JOIN locations l ON p.location_id = l.id
        LEFT JOIN communities c ON p.community_id = c.id
        LEFT JOIN (
            SELECT 
                project_id,
                GROUP_CONCAT(DISTINCT bedroom) AS flat_beds,
                GROUP_CONCAT(DISTINCT bathroom) AS flat_baths,
                GROUP_CONCAT(DISTINCT balcony) AS flat_balcs,
                GROUP_CONCAT(DISTINCT CAST(ROUND(sqft) AS UNSIGNED)) AS flat_sizes
            FROM flats
            WHERE deleted_at IS NULL AND (bedroom > 0 OR bathroom > 0 OR balcony > 0 OR sqft > 0)
            GROUP BY project_id
        ) fl ON p.id = fl.project_id
        LEFT JOIN (
            SELECT pp1.project_id, pp1.total_progress
            FROM project_progress pp1
            INNER JOIN (
                SELECT project_id, MAX(id) AS max_id
                FROM project_progress
                GROUP BY project_id
            ) pp2 ON pp1.id = pp2.max_id
        ) pp ON p.id = pp.project_id
        WHERE {$whereSql}
        ORDER BY p.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $projects = $stmt->fetchAll();

    $projectList = [];

    foreach ($projects as $p) {
        $bedList  = [];
        $bathList = [];
        $balcList = [];
        $sqftList = [];

        // 1. Extract from flats table records
        if (!empty($p['flat_beds'])) {
            foreach (explode(',', (string)$p['flat_beds']) as $v) {
                $n = (int)trim($v);
                if ($n > 0) $bedList[] = $n;
            }
        }
        if (!empty($p['flat_baths'])) {
            foreach (explode(',', (string)$p['flat_baths']) as $v) {
                $n = (int)trim($v);
                if ($n > 0) $bathList[] = $n;
            }
        }
        if (!empty($p['flat_balcs'])) {
            foreach (explode(',', (string)$p['flat_balcs']) as $v) {
                $n = (int)trim($v);
                if ($n > 0) $balcList[] = $n;
            }
        }
        if (!empty($p['flat_sizes'])) {
            foreach (explode(',', (string)$p['flat_sizes']) as $v) {
                $n = (int)trim($v);
                if ($n > 0) $sqftList[] = $n;
            }
        }

        // 2. Extract from floor_wise_unit_meta JSON
        $rawUnits = [];
        if (!empty($p['floor_wise_unit_meta'])) {
            $rawMeta = is_string($p['floor_wise_unit_meta']) 
                ? json_decode($p['floor_wise_unit_meta'], true) 
                : $p['floor_wise_unit_meta'];

            if (is_array($rawMeta)) {
                if (isset($rawMeta['typical_floor']) && is_array($rawMeta['typical_floor'])) {
                    $rawUnits = $rawMeta['typical_floor'];
                } elseif (isset($rawMeta[0]) && is_array($rawMeta[0])) {
                    $rawUnits = $rawMeta;
                } else {
                    foreach ($rawMeta as $val) {
                        if (is_array($val)) {
                            foreach ($val as $subVal) {
                                if (is_array($subVal)) {
                                    $rawUnits[] = $subVal;
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($rawUnits as $u) {
            if (!is_array($u)) continue;

            if (isset($u['bedrooms']) && $u['bedrooms'] !== '' && $u['bedrooms'] !== null) {
                $bNum = (int)$u['bedrooms'];
                if ($bNum > 0) $bedList[] = $bNum;
            }
            if (isset($u['bathrooms']) && $u['bathrooms'] !== '' && $u['bathrooms'] !== null) {
                $bNum = (int)$u['bathrooms'];
                if ($bNum > 0) $bathList[] = $bNum;
            }
            if (isset($u['balcony']) && $u['balcony'] !== '' && $u['balcony'] !== null) {
                $bNum = (int)$u['balcony'];
                if ($bNum > 0) $balcList[] = $bNum;
            }

            $uSqft = $u['sqft'] ?? $u['size'] ?? null;
            if ($uSqft) {
                if (is_numeric($uSqft)) {
                    $sqftList[] = (int)$uSqft;
                } elseif (preg_match('/(\d+)/', str_replace(',', '', (string)$uSqft), $m)) {
                    $sqftList[] = (int)$m[1];
                }
            }
        }

        // 3. Fallback: if beds/baths/balcs empty but sqft exists
        if (empty($bedList) && !empty($sqftList)) {
            foreach ($sqftList as $s) {
                $bedList[]  = ($s >= 1800) ? 4 : (($s >= 1400) ? 3 : (($s >= 800) ? 2 : 1));
                $bathList[] = ($s >= 1800) ? 4 : (($s >= 1400) ? 3 : (($s >= 800) ? 2 : 1));
                $balcList[] = ($s >= 1800) ? 3 : 2;
            }
        }

        // De-duplicate lists
        $bedList  = array_values(array_unique($bedList));
        $bathList = array_values(array_unique($bathList));
        $balcList = array_values(array_unique($balcList));
        $sqftList = array_values(array_unique($sqftList));
        sort($bedList, SORT_NUMERIC);
        sort($bathList, SORT_NUMERIC);
        sort($balcList, SORT_NUMERIC);
        sort($sqftList, SORT_NUMERIC);

        // Apply Unit Filters if requested
        if ($filterBed !== null) {
            if (empty($bedList) || !in_array($filterBed, $bedList)) {
                continue;
            }
        }

        if ($filterBath !== null) {
            if (empty($bathList) || !in_array($filterBath, $bathList)) {
                continue;
            }
        }

        if ($filterBalc !== null) {
            if (empty($balcList) || !in_array($filterBalc, $balcList)) {
                continue;
            }
        }

        if ($minSize !== null || $maxSize !== null) {
            if (empty($sqftList)) {
                continue;
            }
            $hasMatchingSize = false;
            foreach ($sqftList as $s) {
                $matchMin = ($minSize === null || $s >= $minSize);
                $matchMax = ($maxSize === null || $s <= $maxSize);
                if ($matchMin && $matchMax) {
                    $hasMatchingSize = true;
                    break;
                }
            }
            if (!$hasMatchingSize) {
                continue;
            }
        }

        $locationStr = !empty($p['location_name']) ? $p['location_name'] : (!empty($p['project_map_location']) ? $p['project_map_location'] : '');
        $mainImage   = $formatImg($p['image'] ?? '');
        $handover    = !empty($p['handover']) ? $p['handover'] : (!empty($p['expected_handover_date']) ? $p['expected_handover_date'] : 'To be announced');
        $progressPct = $formatPct($p['latest_total_progress'] ?? 0);
        $progressNum = (float)($p['latest_total_progress'] ?? 0);

        $bedStr      = $formatRangeOrVal($bedList);
        $bathStr     = $formatRangeOrVal($bathList);
        $balcStr     = $formatRangeOrVal($balcList);
        $sizeStr     = $formatRangeOrVal($sqftList, 'sqft');
        $uniqueSizes = array_values(array_unique($sqftList));
        sort($uniqueSizes, SORT_NUMERIC);

        $projectList[] = [
            'id'                             => (int)$p['id'],
            'project_name'                   => (string)$p['name'],
            'name'                           => (string)$p['name'],
            'slug'                           => (string)($p['slug'] ?? ''),
            'location'                       => (string)$locationStr,
            'image'                          => (string)$mainImage,
            'image_link'                     => (string)$mainImage,
            'price'                          => (string)($p['price_range'] ?? ''),
            'price_range'                    => (string)($p['price_range'] ?? ''),
            'expected_handover_date'         => (string)$handover,
            'handover_date'                  => (string)$handover,
            'total_completion_percentage'    => (string)$progressPct,
            'total_progress'                 => (string)$progressPct,
            'total_progress_numeric'         => $progressNum,
            'project_sale_status'            => (string)($p['project_sale_status'] ?? 'On Sale'),

            // Project Specifics
            'community'                      => (string)($p['community_name'] ?? ''),
            'community_id'                   => (int)($p['community_id'] ?? 0),
            'flat_size'                      => (string)$sizeStr,
            'bedroom'                        => (string)$bedStr,
            'bathroom'                       => (string)$bathStr,
            'balcony'                        => (string)$balcStr,
        ];
    }

    echo json_encode([
        'success'          => true,
        'message'          => 'Projects list fetched successfully',
        'total_count'      => count($projectList),
        'filters_applied'  => [
            'search'      => !empty($search) ? $search : null,
            'location'    => !empty($location) ? $location : null,
            'community'   => !empty($community) ? $community : null,
            'price_range' => !empty($price) ? $price : null,
            'flat_size'   => !empty($flatSizeRaw) ? $flatSizeRaw : (($minSize !== null || $maxSize !== null) ? "{$minSize}-{$maxSize}" : null),
            'bedroom'     => ($filterBed !== null) ? $filterBed : null,
            'bathroom'    => ($filterBath !== null) ? $filterBath : null,
            'balcony'     => ($filterBalc !== null) ? $filterBalc : null,
            'status'      => !empty($status) ? $status : null,
        ],
        'data'             => $projectList,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

} catch (\PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database Query Error: ' . $e->getMessage(),
        'data'    => [],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (\Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server Error: ' . $e->getMessage(),
        'data'    => [],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
