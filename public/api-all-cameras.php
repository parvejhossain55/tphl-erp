<?php
// API script to serve all cameras with project, location details, and real-time online/offline status.

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
            echo json_encode([
                'success' => false,
                'message' => 'DB Connection failed: ' . $e2->getMessage(),
                'cameras' => [],
            ]);
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
            echo json_encode([
                'success' => false,
                'message' => 'DB Connection failed: ' . $e2->getMessage(),
                'cameras' => [],
            ]);
            exit;
        }
    }
}

// -------------------------------------------------------------
// Helper: Check Cameras Online Status in Parallel with curl_multi
// -------------------------------------------------------------
function checkCamerasParallel(array $urls, int $timeoutSeconds = 2): array
{
    $results = [];
    $uniqueUrls = array_values(array_unique(array_filter($urls)));
    if (empty($uniqueUrls)) {
        return $results;
    }

    $mh = curl_multi_init();
    $curlHandles = [];

    foreach ($uniqueUrls as $url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPGET        => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => $timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) TPHL-StreamChecker/1.0',
            // Read only small initial header/chunks to avoid downloading full streams
            CURLOPT_RANGE          => '0-1024',
        ]);
        curl_multi_add_handle($mh, $ch);
        $curlHandles[$url] = $ch;
    }

    $running = null;
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running > 0) {
            curl_multi_select($mh, 0.05);
        }
    } while ($running > 0 && $status === CURLM_OK);

    $now = date('Y-m-d H:i:s');
    foreach ($curlHandles as $url => $ch) {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // HLS playlist with 200..299 or 206 Partial Content is online
        $isOnline = ($httpCode >= 200 && $httpCode < 300);

        $results[$url] = [
            'is_online'    => $isOnline,
            'live_status'  => $isOnline ? 'online' : 'offline',
            'http_code'    => (int)$httpCode,
            'checked_at'   => $now,
        ];

        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }

    curl_multi_close($mh);
    return $results;
}

try {
    // Support project_id, projectId, or project parameter (single ID or comma-separated)
    $rawProjectId = $_GET['project_id'] ?? $_GET['projectId'] ?? $_GET['project'] ?? null;
    $projectIds = [];

    if ($rawProjectId !== null && trim((string)$rawProjectId) !== '') {
        $parts = explode(',', (string)$rawProjectId);
        foreach ($parts as $p) {
            $p = trim($p);
            if (is_numeric($p) && (int)$p > 0) {
                $projectIds[] = (int)$p;
            }
        }
    }

    $shouldCheck   = !isset($_GET['check_online']) || $_GET['check_online'] !== '0';
    $forceRefresh  = isset($_GET['refresh']) && in_array(strtolower((string)$_GET['refresh']), ['1', 'true', 'yes'], true);

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
    if (!empty($projectIds)) {
        if (count($projectIds) === 1) {
            $sql .= " AND c.project_id = ?";
            $params[] = $projectIds[0];
        } else {
            $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
            $sql .= " AND c.project_id IN ($placeholders)";
            $params = array_merge($params, $projectIds);
        }
    }

    $sql .= " ORDER BY p.name ASC, c.id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // -------------------------------------------------------------
    // Cache Setup (2 Minutes / 120 Seconds TTL)
    // -------------------------------------------------------------
    $cacheDir = __DIR__ . '/../tmp';
    if (!is_dir($cacheDir) || !is_writable($cacheDir)) {
        $cacheDir = sys_get_temp_dir();
    }
    $cacheFile = rtrim($cacheDir, '/\\') . DIRECTORY_SEPARATOR . 'tphl_camera_status_cache.json';
    $cacheTtl = 120; // 2 minutes

    $cachedData = ['updated_at' => 0, 'statuses' => []];
    if (file_exists($cacheFile)) {
        $rawCache = @file_get_contents($cacheFile);
        $decoded = $rawCache ? json_decode($rawCache, true) : null;
        if (is_array($decoded) && isset($decoded['updated_at'], $decoded['statuses'])) {
            $cachedData = $decoded;
        }
    }

    $isCacheValid = !$forceRefresh && (time() - ($cachedData['updated_at'] ?? 0) <= $cacheTtl);
    $statusMap = $cachedData['statuses'] ?? [];

    if ($shouldCheck) {
        $urlsToCheck = [];
        foreach ($rows as $row) {
            $link = trim($row['camera_link'] ?? '');
            if (!empty($link) && (strpos($link, 'http://') === 0 || strpos($link, 'https://') === 0)) {
                // If cache is expired or this URL is not yet in cache, schedule for parallel check
                if (!$isCacheValid || !isset($statusMap[$link])) {
                    $urlsToCheck[] = $link;
                }
            }
        }

        if (!empty($urlsToCheck)) {
            $newStatuses = checkCamerasParallel($urlsToCheck, 2);
            foreach ($newStatuses as $url => $info) {
                $statusMap[$url] = $info;
            }
            // Save updated cache
            @file_put_contents($cacheFile, json_encode([
                'updated_at' => time(),
                'statuses'   => $statusMap,
            ], JSON_UNESCAPED_SLASHES));
            $isCacheValid = false; // Freshly checked
        }
    }

    $onlineCount = 0;
    $offlineCount = 0;

    $cameras = array_map(function($row) use ($shouldCheck, &$statusMap, &$onlineCount, &$offlineCount) {
        $link = trim($row['camera_link'] ?? '');
        $isOnline = false;
        $liveStatus = 'offline';
        $checkedAt = null;

        if (empty($link)) {
            $liveStatus = 'no_link';
        } elseif (!$shouldCheck) {
            $liveStatus = 'unknown';
            $isOnline = true;
        } elseif (isset($statusMap[$link])) {
            $isOnline = (bool)($statusMap[$link]['is_online'] ?? false);
            $liveStatus = (string)($statusMap[$link]['live_status'] ?? 'offline');
            $checkedAt = $statusMap[$link]['checked_at'] ?? null;
        }

        if ($isOnline) {
            $onlineCount++;
        } else {
            $offlineCount++;
        }

        return [
            'id'           => (int)$row['id'],
            'project_id'   => $row['project_id'] !== null ? (int)$row['project_id'] : null,
            'project_name' => (string)$row['project_name'],
            'location'     => (string)$row['location'],
            'camera_name'  => (string)$row['camera_name'],
            'camera_link'  => $link,
            'status'       => (string)$row['status'],
            'is_online'    => $isOnline,
            'live_status'  => $liveStatus,
            'checked_at'   => $checkedAt,
        ];
    }, $rows ?: []);

    $response = [
        'success'           => true,
        'total'             => count($cameras),
        'online_count'      => $onlineCount,
        'offline_count'     => $offlineCount,
        'cached'            => $isCacheValid,
        'cache_ttl_seconds' => $cacheTtl,
    ];

    if (!empty($projectIds)) {
        $response['filter_project_id'] = count($projectIds) === 1 ? $projectIds[0] : $projectIds;
        if (count($projectIds) === 1 && !empty($cameras)) {
            $response['project_name'] = $cameras[0]['project_name'];
            $response['location']     = $cameras[0]['location'];
        }
    }

    $response['cameras'] = $cameras;

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'cameras' => [],
    ]);
}

