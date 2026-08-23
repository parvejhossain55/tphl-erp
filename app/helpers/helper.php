<?php
function uploadImage($input, $module = 'general', $mode = 'original', $width = 300, $height = 300)
{
    return ImageUploader::upload($input, $module, $mode, $width, $height);
}


function getImage($module, $filename)
{
    return ImageUploader::get($module, $filename);
}

/**
 * Return a project image URL with a proper project placeholder fallback.
 * Use this instead of getImage() for project images.
 *
 * @param string|null $filename  Stored filename from projects table
 * @return array ['url' => string, 'missing' => bool]
 */
function getProjectImage($filename)
{
    return ImageUploader::getProject($filename);
}


/**
 * Upload a document/file (PDF, images) without resizing.
 *
 * @param string $input     HTML file input name
 * @param string $module    Folder name inside public/uploads/  (e.g. 'budget_extends')
 * @return string|null      Saved filename on success, null on failure / no file
 */
function uploadFile($input, $module = 'general')
{
    return ImageUploader::uploadFile($input, $module);
}


/**
 * Return a public URL for a file uploaded via uploadFile().
 *
 * @param string $module    Folder name (same as used in uploadFile)
 * @param string|null $filename
 * @return string|null
 */
function getFileUrl($module, $filename)
{
    return ImageUploader::getFileUrl($module, $filename);
}


function paginateLinksSecondary($route, $currentPage, $totalPages)
{
    $currentPage = intval($currentPage);
    $totalPages = intval($totalPages);

    if ($totalPages <= 1)
        return ""; // no need for pagination

    $separator = strpos($route, '?') !== false ? '&' : '?';
    $html = '<nav class="my-4">';
    $html .= '<ul class="pagination pagination-secondary">';

    // Previous Button
    if ($currentPage > 1) {
        $html .= '<li class="page-item">
                    <a class="page-link btn-outline-border" href="' . $route . $separator . 'page=' . ($currentPage - 1) . '">&laquo; Previous</a>
                  </li>';
    } else {
        $html .= '<li class="page-item disabled">
                    <span class="page-link btn-outline-border">« Previous</span>
                  </li>';
    }

    // Page Numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = ($i == $currentPage) ? "active" : "";

        $html .= '<li class="page-item ' . $active . '">
                    <a class="page-link btn-outline-border ' . $active . '" href="' . $route . $separator . 'page=' . $i . '">' . $i . '</a>
                  </li>';
    }

    // Next Button
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item">
                    <a class="page-link btn-outline-border" href="' . $route . $separator . 'page=' . ($currentPage + 1) . '">Next &raquo;</a>
                  </li>';
    } else {
        $html .= '<li class="page-item disabled">
                    <span class="page-link btn-outline-border">Next »</span>
                  </li>';
    }

    $html .= '</ul>';
    $html .= '</nav>';

    return $html;
}



function checkLogin()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_GET['bypass_auth']) && $_GET['bypass_auth'] == 1) {
        $empId = 1;
        $fullName = 'Bypass User';
        $email = 'bypass@example.com';
        if (isset($_GET['email']) && $_GET['email'] !== '') {
            $emailQuery = $_GET['email'];
            $res = db()->query("SELECT id, full_name, email FROM employees WHERE email = '" . db()->real_escape_string($emailQuery) . "' LIMIT 1");
            if ($res && $row = $res->fetch_assoc()) {
                $empId = $row['id'];
                $fullName = $row['full_name'];
                $email = $row['email'];
            }
        }
        $_SESSION['user'] = [
            'id' => 1,
            'full_name' => $fullName,
            'user_name' => 'bypass',
            'email' => $email,
            'role_id' => 1,
            'employee_id' => $empId
        ];
    }

    // Detect AJAX request
    $isAjax = (
        (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (!empty($_POST['ajax']))
        || (!empty($_GET['ajax']))
        || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
    );

    $maxLifetime = 60 * 60 * 24; // 1 day

    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $maxLifetime) {
        session_unset();
        session_destroy();
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'session_expired', 'message' => 'Session expired. Please login again.']);
            exit;
        }
        header("Location: " . $GLOBALS['base_url'] . "/admin-login");
        exit;
    }

    $_SESSION['LAST_ACTIVITY'] = time();

    if (!isset($_SESSION['user'])) {
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'unauthenticated', 'message' => 'Please login to continue.']);
            exit;
        }
        header("Location: " . $GLOBALS['base_url'] . "/admin-login");
        exit;
    }
}


function redirectByRole()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['user']['role_id'])) {
        header("Location: {$GLOBALS['base_url']}/admin-login");
        exit;
    }

    $role = (int) $_SESSION['user']['role_id'];
    $roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

    switch ($role) {
        case 1: // Super Admin
        case 3: // Admin
        case 33: // Chief Officer
            $url = "{$GLOBALS['base_url']}/home";
            break;

        case 2: // Employee
            $url = "{$GLOBALS['base_url']}/employee-dashboard";
            break;

        case 5: // Sales manager as sales team leader
            $url = "{$GLOBALS['base_url']}/sales-dashboard";
            break;

        case 4: // HRM
            $url = "{$GLOBALS['base_url']}/hrm-dashboard";
            break;

        case 6: // Accounts
            $url = "{$GLOBALS['base_url']}/accounts-dashboard";
            break;


        case 7: // Clients Experiment
            $url = "{$GLOBALS['base_url']}/ced-dashboard";
            break;
        case 8: // sales executive
            $url = "{$GLOBALS['base_url']}/my-dashboard";
            break;
        case 9: //sales team header 
            $url = "{$GLOBALS['base_url']}/sales-team-head-dashboard";
            break;
            // case 11: // whatsapp
            //     $url = "{$GLOBALS['base_url']}/whatsapp-dashboard";
            break;
        case 10: // sales
            $url = "{$GLOBALS['base_url']}/digital-marketing-dashboard";
            break;
        case 11: // digital marketing executive
            $url = "{$GLOBALS['base_url']}/digital-marketing-executive-dashboard";
            break;
        case 12: // site Engineer
            $url = "{$GLOBALS['base_url']}/con-site-engineer-dashboard";
            break;
        case 13: // QC
            $url = "{$GLOBALS['base_url']}/qc-dashboard";
            break;
        case 14: // land officer
            $url = "{$GLOBALS['base_url']}/land-dashboard";
            break;
        case 15: // Project Manager
            $url = "{$GLOBALS['base_url']}/con-dashboard";
            break;
        case 16: // Draftsman
            $url = "{$GLOBALS['base_url']}/draft-dashboard";
            break;
        case 17: // Purchase team lead
            $url = "{$GLOBALS['base_url']}/purchase-team-lead-dashboard";
            break;
        case 18: // Project architech dashboard
            $url = "{$GLOBALS['base_url']}/project-architecture-dashboard";
            break;
        case 19: // Estimator
            $url = "{$GLOBALS['base_url']}/estimator-dashboard";
            break;
        case 20: // Operations Manager
        case 32: // Admin Operation
            $url = "{$GLOBALS['base_url']}/operations-dashboard";
            break;
        case 21: // structural designer
            $url = "{$GLOBALS['base_url']}/project-structural-design-dashboard";
            break;
        case 22: //  Project Manager Head
            $url = "{$GLOBALS['base_url']}/con-dashboard";
            break;
        case 23: //  purchase executive
            $url = "{$GLOBALS['base_url']}/purchase-executive-dashboard";
            break;
        case 24: //  qc and internal audit head
            $url = "{$GLOBALS['base_url']}/qc-audit-dashboard";
            break;

        //    case 25: //  qc and internal audit head
        //         $url = "{$GLOBALS['base_url']}/qc-audit-dashboard";
        //         break;
        case 30: // Head Accountant
            $url = "{$GLOBALS['base_url']}/accounts-dashboard";
            break;

        case 31: // Senior Purchase executive
            $url = "{$GLOBALS['base_url']}/sr-executive-dashboard";
            break;

        default:
            $url = "{$GLOBALS['base_url']}/admin-login";
    }

    header("Location: $url");
    exit;
}





/**
 * Search records dynamically
 * 
 * @param mysqli $db
 * @param string $table Table name
 * @param array $fields Fields to search in (e.g., ['full_name', 'phone', 'email'])
 * @param string $searchTerm Term to search
 * @param array $extraConditions Optional: ['column' => 'value']
 * @return array
 */


function searchRecords($db, $table, $fields, $searchTerm, $extraConditions = [])
{
    $searchTerm = "%{$searchTerm}%";

    $whereParts = [];
    $params = [];
    $types = "";

    // Searchable fields
    foreach ($fields as $field) {
        $whereParts[] = "$field LIKE ?";
        $params[] = $searchTerm;
        $types .= "s";
    }

    // Extra conditions (e.g., role_id filter)
    foreach ($extraConditions as $col => $val) {
        $whereParts[] = "$col = ?";
        $params[] = $val;
        $types .= is_int($val) ? "i" : "s";
    }

    $sql = "SELECT * FROM $table";
    if (!empty($whereParts)) {
        $sql .= " WHERE " . implode(" OR ", $whereParts);
    }

    $stmt = $db->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function db()
{
    static $db = null;

    if ($db === null) {
        // config file theke database info load koro
        $config = require dirname(__DIR__) . "/config/config.php";

        $db = new Database($config);
    }
    return $db->conn; // return mysqli connection
}


function getTodayAssignedCount(int $userId): int
{
    $db = $GLOBALS['db'];
    
    $sql = "
        SELECT COUNT(DISTINCT l.id) AS total
        FROM leads AS l
        LEFT JOIN (
            SELECT t1.*
            FROM lead_follow_ups t1
            INNER JOIN (
                SELECT lead_id, MAX(id) AS max_id
                FROM lead_follow_ups
                GROUP BY lead_id
            ) t2 ON t1.id = t2.max_id AND t1.lead_id = t2.lead_id
        ) lf ON lf.lead_id = l.id
        WHERE l.assigned_id = ?
          AND l.deleted_at IS NULL
          AND (
                (l.created_at >= CURDATE() AND l.created_at < CURDATE() + INTERVAL 1 DAY)
                OR
                (l.assigned_at IS NOT NULL AND l.assigned_at >= CURDATE() AND l.assigned_at < CURDATE() + INTERVAL 1 DAY)
              )
          AND NOT (DATE(lf.next_follow_up_date) = CURDATE() AND lf.status = 'completed')
    ";

    $stmt = $db->prepare($sql);
    if (!$stmt) return 0;
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return (int) ($row['total'] ?? 0);
}
function getFollowUpCount(int $userId, string $type = 'today'): int
{
    $userId = (int) $userId;
    $db = $GLOBALS['db'];
    if ($type === 'today') {
        // Only today pending
        $sql = "
            SELECT COUNT(*) AS total
            FROM lead_follow_ups f
            LEFT JOIN leads l ON f.lead_id = l.id
            WHERE f.deleted_at IS NULL
              AND (f.assigned_with = ? OR (f.assigned_with IS NULL AND l.assigned_id = ?))
              AND DATE(f.next_follow_up_date) = CURDATE()
              AND (f.status IS NULL OR f.status = 'pending')
        ";
        $stmt = $db->prepare($sql);
        if (!$stmt)
            return 0;
        $stmt->bind_param("ii", $userId, $userId);
    } elseif ($type === 'upcoming') {
        // Next 3 days upcoming
        $sql = "
            SELECT COUNT(*) AS total
            FROM lead_follow_ups f
            LEFT JOIN leads l ON f.lead_id = l.id
            WHERE f.deleted_at IS NULL
              AND (f.assigned_with = ? OR (f.assigned_with IS NULL AND l.assigned_id = ?))
              AND f.next_follow_up_date > CURDATE()
              AND f.next_follow_up_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
        ";
        $stmt = $db->prepare($sql);
        if (!$stmt)
            return 0;
        $stmt->bind_param("ii", $userId, $userId);
    } else {
        return 0;
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return (int) ($row['total'] ?? 0);
}


function hasPermission($permissionNames)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user']['permissions']) || !is_array($_SESSION['user']['permissions'])) {
        return false;
    }

    if (is_string($permissionNames)) {
        $permissionNames = [$permissionNames];
    }

    $userPermissions = array_map('strtolower', $_SESSION['user']['permissions']);
    $permissionNames = array_map('strtolower', $permissionNames);

    foreach ($permissionNames as $perm) {
        if (in_array($perm, $userPermissions, true)) {
            return true;
        }
    }

    return false;
}

/**
 * Check if the currently logged-in user is a Sales Co-Leader.
 * A co-leader gets lead-list access and can assign leads within their team_id,
 * regardless of role_id. This is set via employees.is_sales_co_leader = 1.
 */
function isSalesCoLeader(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return (int) ($_SESSION['user']['is_sales_co_leader'] ?? 0) === 1;
}


function hasAllPermissions(array $requiredPermissions): bool
{
    // Assume $userPermissions is an array of all the current user's permissions
    global $userPermissions; // adjust according to your app

    foreach ($requiredPermissions as $perm) {
        if (!in_array($perm, $userPermissions)) {
            return false; // missing one → fail
        }
    }
    return true; // has all → pass
}


function isAlreadyHashed($password)
{
    if (strlen($password) === 60 && preg_match('/^\$2[ayb]\$.{56}$/', $password)) {
        return true; // Already hashed
    }
    return false; // Not hashed
}

/**
 * Ensure password is hashed
 * Will hash only if not already hashed
 */
function ensureHashedPassword($password)
{
    if (isAlreadyHashed($password)) {
        return $password; // Return as is if already hashed
    }
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Validate and prepare user data
 */
function prepareUserData($postData, $isFromEmployee = false)
{
    $data = [
        'full_name' => trim($postData['full_name'] ?? ''),
        'user_name' => trim($postData['user_name'] ?? ($postData['username'] ?? '')),
        'phone' => trim($postData['phone'] ?? ''),
        'email' => trim($postData['email'] ?? ''),
        'designation' => trim($postData['designation'] ?? ''),
        'address' => trim($postData['address'] ?? ''),
        'nid' => trim($postData['nid'] ?? ''),
        'role_id' => intval($postData['role_id'] ?? ($isFromEmployee ? 2 : 0)),
        'password' => trim($postData['password'] ?? ''),
        'media_link1' => trim($postData['media_link1'] ?? ($postData['social_media1'] ?? '')),
        'media_link2' => trim($postData['media_link2'] ?? ($postData['social_media2'] ?? '')),
    ];
    return $data;
}

function getUserProfileImage($user)
{
    return ImageUploader::getUserProfile($user);
}



function getLoggedInUserImage()
{
    return ImageUploader::getLoggedInUser();
}

// sweet alert 

function setSuccess($message)
{
    $_SESSION['flash_success'] = $message;
}
function dd($data)
{
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    die;
}
function setError($message)
{
    $_SESSION['flash_error'] = $message;
}

function setErrors(array $errors)
{
    $_SESSION['flash_errors'] = $errors;
}



/**
 * Generate a URL-friendly slug from a name.
 * Automatically ensures uniqueness in the given table & column.
 *
 * @param mysqli $db
 * @param string $name
 * @param string $table
 * @param string $column
 * @param int|null $excludeId
 * @return string
 */

function generateUniqueSlug($db, $name, $table = 'lead_sources', $column = 'slug', $excludeId = null)
{
    $name = trim($name);

    // Split words by space or punctuation (Unicode aware)
    $words = preg_split('/[\s\p{P}]+/u', $name, -1, PREG_SPLIT_NO_EMPTY);

    // Join words with dash
    $slug = implode('-', $words);

    // Lowercase English letters only
    $slug = mb_strtolower($slug, 'UTF-8');
    if (empty($slug)) {
        $slug = 'project-' . time();
    }

    $originalSlug = $slug;
    $i = 1;

    while (slugExists($db, $slug, $table, $column, $excludeId)) {
        $slug = $originalSlug . '-' . $i;
        $i++;
    }

    return $slug;
}



function addCameraWithHls($db, $project_id, $project_name, $camera_name, $rtsp_url, $status = 'active', $hlsBase = __DIR__ . '/hls', $ffmpegPath = 'C:/ffmpeg/bin/ffmpeg.exe')
{
    // 1 Generate slugs
    $project_slug = strtolower(trim(preg_replace('/[^\w]+/', '_', $project_name)));
    $camera_slug = strtolower(trim(preg_replace('/[^\w]+/', '_', $camera_name)));

    // 2 Insert camera into DB WITHOUT hls_path
    $stmt = $db->prepare("INSERT INTO cameras (project_id, camera_name, camera_slug, rtsp_url, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $project_id, $camera_name, $camera_slug, $rtsp_url, $status);
    $stmt->execute();

    // mysqli এর জন্য last inserted ID
    $camera_id = $db->insert_id;

    // 3 Prepare HLS folder
    $projectDir = rtrim($hlsBase, '/') . '/' . $project_slug;
    if (!is_dir($projectDir))
        mkdir($projectDir, 0777, true);

    // 4 HLS file path
    $hlsPath = $projectDir . '/' . $camera_slug . '.m3u8';
    $hlsDbPath = "hls/$project_slug/$camera_slug.m3u8"; // relative path for DB/frontend

    // 5️⃣ Run FFmpeg to generate HLS (background)
    $cmd = "\"$ffmpegPath\" -rtsp_transport tcp -i " . escapeshellarg($rtsp_url) . " "
        . "-c:v copy -c:a aac "
        . "-f hls -hls_time 2 -hls_list_size 6 "
        . "-hls_flags delete_segments+append_list+independent_segments "
        . escapeshellarg($hlsPath) . " > NUL 2>&1 &";

    exec($cmd);

    // 6️⃣ Update DB with HLS path
    $stmt = $db->prepare("UPDATE cameras SET hls_path = ? WHERE id = ?");
    $stmt->bind_param("si", $hlsDbPath, $camera_id);
    $stmt->execute();

    // 7️⃣ Return camera data
    return [
        'id' => $camera_id,
        'project_id' => $project_id,
        'camera_name' => $camera_name,
        'camera_slug' => $camera_slug,
        'rtsp_url' => $rtsp_url,
        'hls_path' => $hlsDbPath,
        'status' => $status
    ];
}



/**
 * Check if slug exists in table.
 *
 * @param mysqli $db
 * @param string $slug
 * @param string $table
 * @param string $column
 * @return bool
 */
function slugExists($db, $slug, $table = 'lead_sources', $column = 'slug', $excludeId = null)
{
    if ($excludeId !== null) {
        $stmt = $db->prepare("SELECT id FROM $table WHERE $column = ? AND id != ?");
        $stmt->bind_param("si", $slug, $excludeId);
    } else {
        $stmt = $db->prepare("SELECT id FROM $table WHERE $column = ?");
        $stmt->bind_param("s", $slug);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result && $result->num_rows > 0;
}


function formatUrl($url)
{
    if (!$url)
        return '#';
    if (!preg_match('~^https?://~i', $url)) {
        return 'https://' . $url;
    }
    return $url;
}



function getSetting($key, $default = null)
{
    static $settings = null;

    if ($settings === null) {
        $db = $GLOBALS['db'];
        $stmt = $db->prepare("SELECT * FROM site_settings ORDER BY id ASC LIMIT 1");
        if ($stmt && $stmt->execute()) {
            $result = $stmt->get_result();
            $settings = $result ? $result->fetch_assoc() : [];
        } else {
            $settings = [];
        }
    }

    return $settings[$key] ?? $default;
}



function calculateReadTime($content, $wpm = 180)
{
    return FormatHelper::calculateReadTime($content, $wpm);
}


function getFlatRulesArray()
{
    static $rulesCache = null;

    if ($rulesCache === null) {
        $db = $GLOBALS['db'];
        $rulesCache = [];
        $query = "SELECT rules_description FROM flat_purchase_rules WHERE status = 1 ORDER BY id ASC";
        $result = $db->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rule = trim($row['rules_description']); // whitespace remove
                if ($rule !== '') { // empty skip
                    $rulesCache[] = $rule;
                }
            }
        }
    }

    return $rulesCache; // array return
}
if (!function_exists('amountInWords')) {
    function amountInWords($amount)
    {
        return FormatHelper::amountInWords($amount);
    }
}

// truncate text to N characters
function truncateText($text, $limit = 50)
{
    return FormatHelper::truncateText($text, $limit);
}


function formatDate($date)
{
    return FormatHelper::formatDate($date);
}

function normalizeDateYear($date)
{
    if (empty($date)) {
        return $date;
    }
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    $year = (int) date('Y', $timestamp);
    if ($year < 100) {
        $month = date('m', $timestamp);
        $day = date('d', $timestamp);
        $correctedYear = 2000 + $year;
        return "$correctedYear-$month-$day";
    }
    return date('Y-m-d', $timestamp);
}

// formant time
function formatPunchTime($time)
{
    return FormatHelper::formatPunchTime($time);
}


// for bd taka decimal format

function format_bdt($number)
{
    return FormatHelper::formatBdt($number);
}

// video url create 
function renderReviewVideo($url, $width = '100%', $height = '315')
{
    if (!$url)
        return '';

    // YouTube
    if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
        $videoId = '';

        if (strpos($url, 'youtu.be') !== false) {
            $videoId = basename($url);
        } else {
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            $videoId = $query['v'] ?? '';
        }

        if ($videoId) {
            return '<iframe width="' . $width . '" height="' . $height . '" src="https://www.youtube.com/embed/' . $videoId . '" frameborder="0" allowfullscreen></iframe>';
        }
    }

    // Vimeo
    if (strpos($url, 'vimeo.com') !== false) {
        $videoId = (int) basename($url);
        if ($videoId) {
            return '<iframe src="https://player.vimeo.com/video/' . $videoId . '" width="' . $width . '" height="' . $height . '" frameborder="0" allowfullscreen></iframe>';
        }
    }

    // Self-hosted video (mp4)
    $ext = pathinfo($url, PATHINFO_EXTENSION);
    if (in_array(strtolower($ext), ['mp4', 'webm', 'ogg'])) {
        return '<video width="' . $width . '" height="' . $height . '" controls><source src="' . $url . '" type="video/' . $ext . '"></video>';
    }

    return '';
}


/* =========================
   SAFE ESCAPE HELPER
   ========================= */
function esc($value, $default = '')
{
    return htmlspecialchars((string) ($value ?? $default), ENT_QUOTES, 'UTF-8');
}

/* =========================
   APPROVAL CONTEXT RENDERER
   ========================= */
function renderApprovalContext(array $req)
{
    if (empty($req['context'])) {
        return;
    }

    $ctx = $req['context'];
    ?>

    <div class="card__wrapper shadow-sm border-0 mb-3">
        <div class="card-header_title">
            <h6 class="mb-2">Details</h6>
        </div>

        <div class="info-card-body">
            <ul class="list-group list-group-flush">

                <!-- Requested By -->
                <?php if (!empty($ctx['requester'])): ?>
                    <li class="px-0 border-0 border-top py-2">
                        <div class="row g-1">
                            <div class="col-12 col-md-6 mb-1 mb-md-0">
                                <span class="badge bg-primary ms-md-2">Requested By</span>
                            </div>
                            <div class="col-12 col-md-6 text-break">
                                <strong><?= esc($ctx['requester']['user_name'] ?? 'N/A') ?></strong>
                                <div class="small text-muted">
                                    <i class="bi bi-envelope"></i>
                                    <?= esc($ctx['requester']['email']) ?>
                                </div>
                                <small class="fw-semibold">
                                    <?= esc($ctx['requester']['designation']) ?>
                                </small>
                            </div>
                        </div>
                    </li>
                <?php endif; ?>

                <!-- Client -->
                <?php if (!empty($ctx['lead'])): ?>
                    <li class="px-0 border-0 border-top py-2">
                        <div class="row g-1">
                            <div class="col-12 col-md-6 mb-1 mb-md-0">
                                <span class="badge bg-info text-light ms-md-2">Client</span>
                            </div>
                            <div class="col-12 col-md-6">
                                <strong><?= esc($ctx['lead']['full_name']) ?></strong>
                                <div class="small text-muted">
                                    <i class="bi bi-telephone"></i>
                                    <?= esc($ctx['lead']['phone'], 'N/A') ?>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endif; ?>

                <!-- Project / Flat -->
                <?php if (!empty($ctx['flat'])): ?>
                    <li class="px-0 border-0 border-top py-2">
                        <div class="row g-1">
                            <div class="col-12 col-md-6 mb-1 mb-md-0">
                                <span class="badge bg-success ms-md-2">Project</span>
                            </div>
                            <div class="col-12 col-md-6 text-break">
                                <strong><?= esc($ctx['flat']['project_name'], 'Unknown Project') ?></strong>
                                <div class="small text-muted">
                                    <?= esc($ctx['flat']['location_name']) ?>
                                </div>
                                <div>
                                    <?= esc($ctx['flat']['flat_name']) ?>,
                                    <small class="text-muted">
                                        <?= esc($ctx['flat']['sqft'], 0) ?> sqft
                                    </small>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endif; ?>

                <!-- Employee -->
                <?php if (!empty($ctx['employee'])): ?>
                    <li class="px-0 border-0 border-top py-2">
                        <div class="row g-1">
                            <div class="col-12 col-md-6 mb-1 mb-md-0">
                                <span class="badge bg-warning text-dark ms-md-2">Employee</span>
                            </div>
                            <div class="col-12 col-md-6">
                                <strong><?= esc($ctx['employee']['user_name'] ?? 'N/A') ?></strong>
                                <div class="small text-muted">
                                    <i class="bi bi-briefcase"></i>
                                    <?= esc($ctx['employee']['designation'], 'N/A') ?>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endif; ?>

                <!-- Salary -->
                <?php if (!empty($ctx['salary'])): ?>
                    <li class="px-0 border-0 border-top py-2">
                        <div class="row g-1 mb-1">
                            <div class="col-12 col-md-6">
                                <span class="badge bg-secondary ms-md-2">Salary Month</span>
                            </div>
                            <div class="col-12 col-md-6">
                                <strong><?= esc($ctx['salary']['salary_month'], '-') ?></strong>
                            </div>
                        </div>
                        <div class="row g-1">
                            <div class="col-12 col-md-6">
                                <span class="badge bg-secondary ms-md-2">Gross Salary</span>
                            </div>
                            <div class="col-12 col-md-6">
                                <strong class="text-success">
                                    ৳ <?= number_format($ctx['salary']['gross_salary'] ?? 0, 2) ?>
                                </strong>
                            </div>
                        </div>
                    </li>
                <?php endif; ?>

                <!-- Leave -->
                <?php if (!empty($ctx['leave'])): ?>
                    <li class="list-group mt-3 px-0 border-0 border-top mt-2">
                        <div class="row align-items-center my-2">
                            <div class="col-6 col-md-5 col-lg-6">
                                <span class="badge bg-danger ">Leave Type</span>
                            </div>
                            <div class="col-6 col-md-6 col-lg-6">
                                <strong><?= esc($ctx['leave']['leave_type'], 'N/A') ?></strong>
                            </div>
                        </div>

                        <div class="row align-items-center">
                            <div class="col-6 col-md-5 col-lg-6">
                                <span class="badge bg-danger ms-2">Duration</span>
                            </div>
                            <div class="col-6 col-md-6 col-lg-6">
                                <strong><?= esc($ctx['leave']['requested_days'], 0) ?> days</strong>
                            </div>
                        </div>
                    </li>
                <?php endif; ?>

                <!-- Manpower Requisition -->
                <?php if (!empty($ctx['requisition'])): ?>
                    <li class="px-0 border-0 border-top py-2">
                        <div class="row g-1 mb-1">
                            <div class="col-12 col-md-6 mb-1 mb-md-0">
                                <span class="badge bg-primary ms-md-2">Job Title</span>
                            </div>
                            <div class="col-12 col-md-6">
                                <strong><?= esc($ctx['requisition']['job_title'], 'N/A') ?></strong>
                            </div>
                        </div>
                        <div class="row g-1 mb-1">
                            <div class="col-12 col-md-6 mb-1 mb-md-0">
                                <span class="badge bg-secondary ms-md-2">Vacancies</span>
                            </div>
                            <div class="col-12 col-md-6">
                                <strong><?= esc($ctx['requisition']['no_of_vacancies'], 0) ?></strong>
                            </div>
                        </div>
                        <?php if (!empty($ctx['requisition']['project_name'])): ?>
                            <div class="row g-1 mb-1">
                                <div class="col-12 col-md-6 mb-1 mb-md-0">
                                    <span class="badge bg-info text-light ms-md-2">Project</span>
                                </div>
                                <div class="col-12 col-md-6">
                                    <strong><?= esc($ctx['requisition']['project_name'], 'N/A') ?></strong>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="row g-1">
                            <div class="col-12 col-md-6 mb-1 mb-md-0">
                                <span class="badge bg-dark ms-md-2">Department</span>
                            </div>
                            <div class="col-12 col-md-6">
                                <strong><?= esc($ctx['requisition']['department_name'], 'N/A') ?></strong>
                            </div>
                        </div>
                    </li>
                <?php endif; ?>

                <!-- Meta -->
                <?php if (!empty($ctx['meta'])): ?>
                    <?php foreach ($ctx['meta'] as $key => $value): ?>
                        <li class="px-0 border-0 border-top py-2">
                            <div class="row g-1">
                                <div class="col-12 col-md-6 mb-1 mb-md-0">
                                    <span class="badge bg-secondary ms-md-2">
                                        <?= esc(ucfirst(str_replace('_', ' ', $key))) ?>
                                    </span>
                                </div>
                                <div class="col-12 col-md-6">
                                    <strong><?= esc($value, 'N/A') ?></strong>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>

            </ul>
        </div>
    </div>


    <?php
}


//mail
if (!function_exists('sendMail')) {
    function sendMail($email, $title, $link, $assignedBy)
    {
        require_once __DIR__ . '/../core/MailServiceForPHPMailer.php';
        $mail = new MailService();
        return $mail->sendMail($email, $title, $link, $assignedBy);
    }
}

if (!function_exists('sendGenericMail')) {
    function sendGenericMail($email, $subject, $body)
    {
        require_once __DIR__ . '/../core/MailServiceForPHPMailer.php';
        $mail = new MailService();
        return $mail->sendGenericMail($email, $subject, $body);
    }
}

function generateVerificationCode($length = 6)
{
    return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

// upload webp image
function uploadProjectImage($inputName, $quality = 75, $maxWidth = 1000)
{
    return ImageUploader::uploadProject($inputName, $quality, $maxWidth);
}

if (!function_exists('cpFmt')) {
    function cpFmt($n)
    {
        return FormatHelper::cpFmt($n);
    }
}

function checkTipsoiEmployeeMatch($db)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['user']['is_tipsoi_employee'])) {
        return $_SESSION['user']['is_tipsoi_employee'];
    }

    if (empty($_SESSION['user']['id'])) {
        return false;
    }

    $loggedUserId = (int) $_SESSION['user']['id'];
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $loggedUserId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user || empty($user['employee_id'])) {
        $_SESSION['user']['is_tipsoi_employee'] = false;
        return false;
    }

    $empId = (int) $user['employee_id'];
    $stmt = $db->prepare("SELECT * FROM employees WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $empId);
    $stmt->execute();
    $employee = $stmt->get_result()->fetch_assoc();
    if (!$employee || empty($employee['email']) || empty($employee['employee_id'])) {
        $_SESSION['user']['is_tipsoi_employee'] = false;
        return false;
    }

    $localEmail = strtolower(trim($employee['email']));
    $localOfficeId = trim((string) $employee['employee_id']);

    $normalizeIdPhp = function ($id) {
        $str = trim((string) $id);
        return preg_match('/^\d+$/', $str) ? (string) intval($str) : $str;
    };
    $normOfficeId = $normalizeIdPhp($localOfficeId);

    // Call Tipsoi API
    try {
        $apiService = new ExternalApiService($db);
        $token = $apiService->getBearerToken();
        if (!$token) {
            return false;
        }

        $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/employee?order=asc&pageNumber=0&perPage=2000&status=1");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6); // short timeout so it doesn't block the UI
        $res = curl_exec($ch);
        curl_close($ch);

        $empData = json_decode($res, true);
        $externalEmployees = $empData['data'] ?? $empData['employees'] ?? $empData['content'] ?? $empData ?? [];

        foreach ($externalEmployees as $emp) {
            $empEmail = strtolower(trim($emp['email'] ?? ''));
            $empOid = $normalizeIdPhp($emp['employeeOfficeId'] ?? $emp['employeeId'] ?? '');

            if ($empEmail === $localEmail && $empOid === $normOfficeId) {
                $_SESSION['user']['is_tipsoi_employee'] = true;
                $_SESSION['user']['tipsoi_office_id'] = $empOid;
                $_SESSION['user']['tipsoi_id'] = $emp['id'] ?? $emp['employeeId'] ?? null;
                return true;
            }
        }
    } catch (Exception $e) {
        // fail silently
    }

    $_SESSION['user']['is_tipsoi_employee'] = false;
    return false;
}

/**
 * Check if the currently logged-in user is an Accounts Head (Role ID 30)
 */
function isAccountsHead(): bool
{
    return isset($_SESSION['user']['role_id']) && (int) $_SESSION['user']['role_id'] === 30;
}

/**
 * Check if the currently logged-in user is Admin Operation (Role ID 32)
 */
function isAdminOperation(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user']['role_id']) && (int) $_SESSION['user']['role_id'] === 32;
}

/**
 * Check if the currently logged-in user is an Operation role (Role ID 20 or 32)
 */
function isOperationRole(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $roleId = (int) ($_SESSION['user']['role_id'] ?? 0);
    return in_array($roleId, [20, 32], true);
}

/**
 * Check if the current user can approve operational processes.
 * Returns false for Role ID 32 (Admin Operation), true otherwise.
 */
function canApproveProcess(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $roleId = (int) ($_SESSION['user']['role_id'] ?? 0);
    if ($roleId === 32) {
        return false;
    }
    return true;
}
