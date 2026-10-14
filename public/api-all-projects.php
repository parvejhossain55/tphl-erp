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

try {
    $whereClauses = ["p.deleted_at IS NULL"];
    $params       = [];

    // 1. Search Filter (by Project Name, Slug, Description, or Location)
    $search = trim($_GET['search'] ?? $_GET['keyword'] ?? $_GET['q'] ?? '');
    if (!empty($search)) {
        $whereClauses[] = "(p.name LIKE ? OR p.slug LIKE ? OR l.name LIKE ? OR p.project_map_location LIKE ?)";
        $searchTerm = "%{$search}%";
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

    // 3. Price Filter (by Exact match or partial match on price_range)
    $price = trim($_GET['price'] ?? $_GET['price_range'] ?? '');
    if (!empty($price)) {
        $whereClauses[] = "p.price_range LIKE ?";
        $params[] = "%{$price}%";
    }

    // 4. Sale Status Filter (Optional e.g. "On Sale", "Sold Out")
    $status = trim($_GET['status'] ?? $_GET['sale_status'] ?? '');
    if (!empty($status)) {
        $whereClauses[] = "p.project_sale_status = ?";
        $params[] = $status;
    }

    $whereSql = implode(' AND ', $whereClauses);

    $sql = "
        SELECT 
            p.*,
            COALESCE(l.name, '') AS location_name,
            COALESCE(pp.total_progress, 0.00) AS latest_total_progress
        FROM projects p
        LEFT JOIN locations l ON p.location_id = l.id
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
        $locationStr = !empty($p['location_name']) ? $p['location_name'] : (!empty($p['project_map_location']) ? $p['project_map_location'] : '');
        $mainImage   = $formatImg($p['image'] ?? '');
        $handover    = !empty($p['handover']) ? $p['handover'] : (!empty($p['expected_handover_date']) ? $p['expected_handover_date'] : 'To be announced');
        $progressPct = $formatPct($p['latest_total_progress'] ?? 0);
        $progressNum = (float)($p['latest_total_progress'] ?? 0);

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
        ];
    }

    echo json_encode([
        'success'          => true,
        'message'          => 'Projects list fetched successfully',
        'total_count'      => count($projectList),
        'filters_applied'  => [
            'search'   => !empty($search) ? $search : null,
            'location' => !empty($location) ? $location : null,
            'price'    => !empty($price) ? $price : null,
            'status'   => !empty($status) ? $status : null,
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
