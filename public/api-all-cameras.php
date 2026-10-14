<?php
// Simple API script to serve all cameras with project and location details.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database credentials for KVM 8 with fallback to local development
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
        echo json_encode([
            'success' => false,
            'message' => 'DB Connection failed: ' . $e2->getMessage(),
            'cameras' => [],
        ]);
        exit;
    }
}

try {
    $projectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;

    $sql = "
        SELECT 
            c.id,
            c.project_id,
            COALESCE(p.name, '') AS project_name,
            COALESCE(l.name, '') AS location,
            COALESCE(c.camera_name, '') AS camera_name,
            COALESCE(NULLIF(TRIM(c.web_link), ''), NULLIF(TRIM(c.rtsp_url), '')) AS camera_link,
            c.status
        FROM cameras c
        LEFT JOIN projects p ON c.project_id = p.id
        LEFT JOIN locations l ON p.location_id = l.id
        WHERE c.status = 'active'
          AND (p.deleted_at IS NULL OR p.id IS NULL)
    ";

    $params = [];
    if ($projectId !== null && $projectId > 0) {
        $sql .= " AND c.project_id = ?";
        $params[] = $projectId;
    }

    $sql .= " ORDER BY p.name ASC, c.id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $cameras = array_map(function($row) {
        return [
            'id'           => (int)$row['id'],
            'project_id'   => $row['project_id'] !== null ? (int)$row['project_id'] : null,
            'project_name' => (string)$row['project_name'],
            'location'     => (string)$row['location'],
            'camera_name'  => (string)$row['camera_name'],
            'camera_link'  => (string)($row['camera_link'] ?? ''),
            'status'       => (string)$row['status'],
        ];
    }, $rows ?: []);

    echo json_encode([
        'success' => true,
        'total'   => count($cameras),
        'cameras' => $cameras,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'cameras' => [],
    ]);
}
