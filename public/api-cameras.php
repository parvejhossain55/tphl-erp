<?php
// Simple API script to serve project cameras, bypassing the need for Remote MySQL.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database credentials for KVM 8
$host = '127.0.0.1';
$db   = 'final_erp_db';
$user = 'final_erp_user';
$pass = '#erp_db@TPHL_PASS';
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
