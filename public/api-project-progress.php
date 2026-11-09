<?php
// Simple API script to serve project progress details.

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
        echo json_encode([
            'success' => false,
            'message' => 'DB Connection failed: ' . $e2->getMessage(),
            'data'    => null,
        ]);
        exit;
    }
}

try {
    $propertyId = (int)($_GET['property_id'] ?? $_GET['project_id'] ?? $_GET['id'] ?? 0);

    $sql = "
        SELECT 
            pp.id,
            pp.project_id AS property_id,
            COALESCE(p.name, '') AS property_name,
            COALESCE(l.name, '') AS location,
            pp.last_updated_date,
            pp.last_month_target,
            pp.last_month_achieved,
            pp.structural_work,
            pp.plaster_work,
            pp.brick_work,
            pp.electrical_work,
            pp.plumbing_work,
            pp.tiles_work,
            pp.paint_work,
            pp.total_progress
        FROM project_progress pp
        LEFT JOIN projects p ON pp.project_id = p.id
        LEFT JOIN locations l ON p.location_id = l.id
        WHERE (p.deleted_at IS NULL OR p.id IS NULL)
    ";

    $params = [];
    if ($propertyId > 0) {
        $sql .= " AND pp.project_id = ? ORDER BY pp.id DESC LIMIT 1";
        $params[] = $propertyId;
    } else {
        $sql .= " ORDER BY p.name ASC, pp.id DESC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $formatRow = function($row) {
        $formatPct = function($val) {
            $num = (float)$val;
            return (floor($num) == $num ? (int)$num : number_format($num, 1)) . '%';
        };

        return [
            'property_id'        => (int)$row['property_id'],
            'property_name'      => (string)$row['property_name'],
            'location'           => (string)$row['location'],
            'lastUpdatedDate'    => (string)($row['last_updated_date'] ?: date('Y-m-d')),
            'lastMonthTarget'    => $formatPct($row['last_month_target'] ?? 0),
            'lastMonthAchieved'  => $formatPct($row['last_month_achieved'] ?? 0),
            'totalProgress'      => $formatPct($row['total_progress'] ?? 0),
            'work_category_progress' => [
                'structural_work' => $formatPct($row['structural_work'] ?? 0),
                'plaster_work'    => $formatPct($row['plaster_work'] ?? 0),
                'brick_work'      => $formatPct($row['brick_work'] ?? 0),
                'electrical_work' => $formatPct($row['electrical_work'] ?? 0),
                'plumbing_work'   => $formatPct($row['plumbing_work'] ?? 0),
                'tiles_work'      => $formatPct($row['tiles_work'] ?? 0),
                'paint_work'      => $formatPct($row['paint_work'] ?? 0),
            ],
        ];
    };

    if ($propertyId > 0) {
        $row = $stmt->fetch();
        if ($row) {
            $data = $formatRow($row);
            echo json_encode([
                'success' => true,
                'data'    => $data,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            // Check if project itself exists
            $checkProj = $pdo->prepare("SELECT id, name FROM projects WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $checkProj->execute([$propertyId]);
            $proj = $checkProj->fetch();

            if ($proj) {
                // Return default zero structure for valid project without progress yet
                echo json_encode([
                    'success' => true,
                    'data'    => [
                        'property_id'        => (int)$proj['id'],
                        'property_name'      => (string)$proj['name'],
                        'location'           => '',
                        'lastUpdatedDate'    => date('Y-m-d'),
                        'lastMonthTarget'    => '0%',
                        'lastMonthAchieved'  => '0%',
                        'totalProgress'      => '0%',
                        'work_category_progress' => [
                            'structural_work' => '0%',
                            'plaster_work'    => '0%',
                            'brick_work'      => '0%',
                            'electrical_work' => '0%',
                            'plumbing_work'   => '0%',
                            'tiles_work'      => '0%',
                            'paint_work'      => '0%',
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Project not found',
                    'data'    => null,
                ]);
            }
        }
    } else {
        $rows = $stmt->fetchAll();
        $list = array_map($formatRow, $rows ?: []);

        echo json_encode([
            'success' => true,
            'total'   => count($list),
            'data'    => $list,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data'    => null,
    ]);
}
