<?php
// API endpoint: Update Client/User Profile Data
// Supports updating Name, Email, Phone Number, Address, and Image.
// URL: /public/api-update-user-data.php (or /api-update-user-data.php)

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, x-api-key');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// -------------------------------------------------------------
// Database Connection (Dynamic config / local vs production fallback)
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

if (!$pdo) {
    if ($isLocal) {
        try {
            $pdo = new PDO("mysql:host=127.0.0.1;dbname=tphl_db;charset=utf8mb4", 'root', '', $options);
        } catch (\PDOException $e) {
            try {
                $pdo = new PDO("mysql:host=localhost;dbname=tphl_db;charset=utf8mb4", 'root', '', $options);
            } catch (\PDOException $e2) {
                try {
                    $pdo = new PDO("mysql:host=127.0.0.1;dbname=final_erp_db;charset=utf8mb4", 'final_erp_user', '#erp_db@TPHL_PASS', $options);
                } catch (\PDOException $e3) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'DB Connection failed: ' . $e3->getMessage(),
                        'data'    => null
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit;
                }
            }
        }
    } else {
        try {
            $pdo = new PDO("mysql:host=127.0.0.1;dbname=final_erp_db;charset=utf8mb4", 'final_erp_user', '#erp_db@TPHL_PASS', $options);
        } catch (\PDOException $e) {
            try {
                $pdo = new PDO("mysql:host=127.0.0.1;dbname=tphl_db;charset=utf8mb4", 'root', '', $options);
            } catch (\PDOException $e2) {
                echo json_encode([
                    'success' => false,
                    'message' => 'DB Connection failed: ' . $e2->getMessage(),
                    'data'    => null
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
        }
    }
}

// Base URL calculation
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $isLocal ? "{$protocol}://{$host}/TPHL-ERP" : "{$protocol}://{$host}";

// -------------------------------------------------------------
// Request Parameters Parsing (supports $_POST, $_FILES and JSON)
// -------------------------------------------------------------
$rawInput = file_get_contents('php://input');
$jsonBody = !empty($rawInput) ? json_decode($rawInput, true) : [];
if (!is_array($jsonBody)) $jsonBody = [];

// Client identifier: user_id is the lead_id
$leadId = (int)(
    $_POST['user_id'] 
    ?? $_POST['lead_id'] 
    ?? $_POST['id'] 
    ?? $_POST['client_id']
    ?? $jsonBody['user_id'] 
    ?? $jsonBody['lead_id'] 
    ?? $jsonBody['id'] 
    ?? $jsonBody['client_id']
    ?? $_GET['user_id'] 
    ?? $_GET['lead_id'] 
    ?? $_GET['id'] 
    ?? 0
);

$identifierPhone = trim($_POST['identifier_phone'] ?? $jsonBody['identifier_phone'] ?? $_GET['identifier_phone'] ?? '');
$identifierEmail = trim($_POST['identifier_email'] ?? $jsonBody['identifier_email'] ?? $_GET['identifier_email'] ?? '');

// -------------------------------------------------------------
// Find Client (leads table)
// -------------------------------------------------------------
$lead = null;

if ($leadId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ? LIMIT 1");
    $stmt->execute([$leadId]);
    $lead = $stmt->fetch();
} elseif (!empty($identifierPhone)) {
    $cleanPhone = preg_replace('/[^0-9]/', '', $identifierPhone);
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE phone LIKE ? OR phone_number LIKE ? LIMIT 1");
    $stmt->execute(["%$cleanPhone%", "%$cleanPhone%"]);
    $lead = $stmt->fetch();
} elseif (!empty($identifierEmail)) {
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE LOWER(email) = LOWER(?) LIMIT 1");
    $stmt->execute([$identifierEmail]);
    $lead = $stmt->fetch();
}

if (!$lead) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Client not found. Please provide a valid user_id (lead_id), phone, or email.',
        'data'    => null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$leadId = (int)$lead['id'];

// -------------------------------------------------------------
// Collect Fields to Update
// -------------------------------------------------------------
$updateFields = [];
$params = [];

// 1. Name
$name = $_POST['name'] ?? $_POST['full_name'] ?? $_POST['client_name'] ?? $jsonBody['name'] ?? $jsonBody['full_name'] ?? $jsonBody['client_name'] ?? null;
if ($name !== null && trim($name) !== '') {
    $trimmedName = trim($name);
    $updateFields[] = "full_name = ?";
    $params[] = $trimmedName;
    $updateFields[] = "lead_name = ?";
    $params[] = $trimmedName;
}

// 2. Email
$email = $_POST['email'] ?? $jsonBody['email'] ?? null;
if ($email !== null && trim($email) !== '') {
    $trimmedEmail = trim($email);
    $updateFields[] = "email = ?";
    $params[] = $trimmedEmail;
    
    // Also sync with onboard_clients if exists
    try {
        $syncOc = $pdo->prepare("UPDATE onboard_clients SET email = ?, login_email = ? WHERE lead_id = ?");
        $syncOc->execute([$trimmedEmail, $trimmedEmail, $leadId]);
    } catch (\Exception $e) {
        // Continue safely
    }
}

// 3. Phone Number
$phone = $_POST['phone'] ?? $_POST['phone_number'] ?? $jsonBody['phone'] ?? $jsonBody['phone_number'] ?? null;
if ($phone !== null && trim($phone) !== '') {
    $trimmedPhone = trim($phone);
    $updateFields[] = "phone = ?";
    $params[] = $trimmedPhone;
    $updateFields[] = "phone_number = ?";
    $params[] = $trimmedPhone;
}

// 4. Address
$address = $_POST['address'] ?? $_POST['present_address'] ?? $jsonBody['address'] ?? $jsonBody['present_address'] ?? null;
if ($address !== null) {
    $trimmedAddress = trim($address);
    $updateFields[] = "address = ?";
    $params[] = $trimmedAddress;

    // Check if present_address column exists in leads table dynamically
    try {
        $checkCol = $pdo->query("SHOW COLUMNS FROM leads LIKE 'present_address'")->fetch();
        if ($checkCol) {
            $updateFields[] = "present_address = ?";
            $params[] = $trimmedAddress;
        }
    } catch (\Exception $e) {
        // Fallback safely
    }
}

// 5. Image (File upload or Base64 string)
$uploadedImageName = null;
$uploadDir = __DIR__ . '/uploads/leads/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

// A. Check Multipart $_FILES
$imageFile = $_FILES['image'] ?? $_FILES['photo'] ?? $_FILES['lead_image'] ?? null;
if ($imageFile && is_array($imageFile) && ($imageFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($imageFile['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (in_array($ext, $allowed)) {
        $uploadedImageName = 'lead_' . $leadId . '_' . time() . '.' . $ext;
        move_uploaded_file($imageFile['tmp_name'], $uploadDir . $uploadedImageName);
    }
}

// B. Check Base64 string
if (!$uploadedImageName) {
    $base64Image = $_POST['image'] ?? $_POST['photo'] ?? $_POST['lead_image'] ?? $jsonBody['image'] ?? $jsonBody['photo'] ?? $jsonBody['lead_image'] ?? null;
    if ($base64Image && is_string($base64Image) && strpos($base64Image, 'base64,') !== false) {
        $parts = explode('base64,', $base64Image);
        $imageType = 'png';
        if (preg_match('/data:image\/([a-zA-Z0-9]+);/', $parts[0], $match)) {
            $imageType = strtolower($match[1]) === 'jpeg' ? 'jpg' : strtolower($match[1]);
        }
        $decoded = base64_decode($parts[1]);
        if ($decoded !== false) {
            $uploadedImageName = 'lead_' . $leadId . '_' . time() . '.' . $imageType;
            file_put_contents($uploadDir . $uploadedImageName, $decoded);
        }
    }
}

if ($uploadedImageName) {
    $updateFields[] = "lead_image = ?";
    $params[] = $uploadedImageName;
}

if (empty($updateFields)) {
    echo json_encode([
        'success' => false,
        'message' => 'No fields provided to update.',
        'data'    => null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$updateFields[] = "updated_at = NOW()";

// -------------------------------------------------------------
// Execute Update
// -------------------------------------------------------------
try {
    $sql = "UPDATE leads SET " . implode(", ", $updateFields) . " WHERE id = ?";
    $params[] = $leadId;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Refetch refreshed data
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ? LIMIT 1");
    $stmt->execute([$leadId]);
    $updatedLead = $stmt->fetch();

    $finalImageName = $updatedLead['lead_image'] ?? '';
    $finalImageUrl = '';
    if ($finalImageName) {
        $finalImageUrl = str_starts_with($finalImageName, 'http') 
            ? $finalImageName 
            : "{$baseUrl}/public/uploads/leads/{$finalImageName}";
    } else {
        $finalImageUrl = "{$baseUrl}/public/assets/images/avatar/avatar.png";
    }

    echo json_encode([
        'success' => true,
        'message' => 'Client data updated successfully.',
        'data'    => [
            'user_id'     => (int)$updatedLead['id'],
            'lead_id'     => (int)$updatedLead['id'],
            'name'        => $updatedLead['full_name'] ?? '',
            'phone'       => !empty($updatedLead['phone']) ? $updatedLead['phone'] : ($updatedLead['phone_number'] ?? ''),
            'email'       => $updatedLead['email'] ?? '',
            'address'     => $updatedLead['address'] ?? ($updatedLead['present_address'] ?? ''),
            'lead_image'  => $finalImageName,
            'image'       => $finalImageUrl
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update client data: ' . $e->getMessage(),
        'data'    => null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
