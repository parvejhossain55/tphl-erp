<?php
// Simple API script to serve project cameras, bypassing the need for Remote MySQL.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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

// 2. Fallback to standard presets if config didn't connect
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
}

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'DB Connection failed: Unable to connect to ERP Database']);
    exit;
}

$projectId = (int)($_GET['project_id'] ?? $_GET['id'] ?? 0);

if ($projectId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'project_id is required',
        'cameras' => [],
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT COALESCE(NULLIF(TRIM(web_link), ''), NULLIF(TRIM(rtsp_url), '')) AS camera_url 
        FROM cameras 
        WHERE project_id = ? AND status = 'active' 
        ORDER BY id ASC
    ");
    $stmt->execute([$projectId]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $cameras = $rows ? array_values(array_filter($rows)) : [];

    echo json_encode([
        'success' => true,
        'project_id' => $projectId,
        'cameras' => $cameras,
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'cameras' => [],
    ]);
}
