<?php
function uploadImage($input, $module = 'general', $mode = 'original',  $width = 300,   $height = 300)
{
    // 1. File check
    if (!isset($_FILES[$input]) || $_FILES[$input]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // 2. Allowed extensions
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($_FILES[$input]['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        return null;
    }

    // 3. Upload path
    $uploadPath = dirname(__DIR__, 2) . "/public/uploads/{$module}/";
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0777, true);
    }

    // 4. File name
    $fileName = uniqid($module . '_') . '.' . $ext;
    $target = $uploadPath . $fileName;

    // ======================
    // ORIGINAL MODE
    // ======================
    if ($mode === 'original') {
        return move_uploaded_file($_FILES[$input]['tmp_name'], $target)
            ? $fileName
            : null;
    }

    // ======================
    // FIXED MODE (Safe)
    // ======================
    // GD not available → fallback
    if (!extension_loaded('gd')) {
        return move_uploaded_file($_FILES[$input]['tmp_name'], $target)
            ? $fileName
            : null;
    }

    // Create source image
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $src = imagecreatefromjpeg($_FILES[$input]['tmp_name']);
            break;
        case 'png':
            $src = imagecreatefrompng($_FILES[$input]['tmp_name']);
            break;
        case 'gif':
            $src = imagecreatefromgif($_FILES[$input]['tmp_name']);
            break;
        case 'webp':
            if (function_exists('imagecreatefromwebp')) {
                $src = imagecreatefromwebp($_FILES[$input]['tmp_name']);
            } else {
                return move_uploaded_file($_FILES[$input]['tmp_name'], $target)
                    ? $fileName
                    : null;
            }
            break;
        default:
            return null;
    }

    if (!$src) {
        return null;
    }

    // Destination canvas
    $dst = imagecreatetruecolor($width, $height);

    // Transparency support
    if (in_array($ext, ['png', 'gif', 'webp'])) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $width, $height, $transparent);
    }

    // Resize
    [$w, $h] = getimagesize($_FILES[$input]['tmp_name']);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $w, $h);

    // Save image
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($dst, $target, 90);
            break;
        case 'png':
            imagepng($dst, $target);
            break;
        case 'gif':
            imagegif($dst, $target);
            break;
        case 'webp':
            imagewebp($dst, $target, 90);
            break;
    }

    imagedestroy($src);
    imagedestroy($dst);

    return $fileName;
}


function getImage($module, $filename)
{
    if (!$filename) {
        return $GLOBALS['base_url'] . '/public/assets/images/avatar/avatar.png';
    }

    return $GLOBALS['base_url'] . "/uploads/{$module}/{$filename}";
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
    // No file uploaded or upload error → skip silently
    if (!isset($_FILES[$input]) || $_FILES[$input]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'doc', 'docx', 'xlsx', 'xls'];
    $ext     = strtolower(pathinfo($_FILES[$input]['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        return null;
    }

    $uploadPath = dirname(__DIR__, 2) . "/public/uploads/{$module}/";
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0777, true);
    }

    $fileName = uniqid($module . '_') . '.' . $ext;
    $target   = $uploadPath . $fileName;

    return move_uploaded_file($_FILES[$input]['tmp_name'], $target) ? $fileName : null;
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
    if (!$filename) {
        return null;
    }
    return $GLOBALS['base_url'] . "/public/uploads/{$module}/{$filename}";
}


function paginateLinksSecondary($route, $currentPage, $totalPages)
{
    $currentPage = intval($currentPage);
    $totalPages  = intval($totalPages);

    if ($totalPages <= 1) return ""; // no need for pagination

    $separator = strpos($route, '?') !== false ? '&' : '?';
    $html  = '<nav class="my-4">';
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

    $maxLifetime = 60 * 60 * 24; // 1 day

    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $maxLifetime) {
        session_unset();
        session_destroy();
        header("Location: " . $GLOBALS['base_url'] . "/admin-login");
        exit;
    }

    $_SESSION['LAST_ACTIVITY'] = time();

    if (!isset($_SESSION['user'])) {
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

    switch ($role) {
        case 1: // Super Admin
        case 3: // Admin
            $url = "{$GLOBALS['base_url']}/home";
            break;

        case 2: // Employee
            $url = "{$GLOBALS['base_url']}/employee-dashboard";
            break;

        case 5: // Sales
            $url = "{$GLOBALS['base_url']}/sales-dashboard";
            break;

        case 4: // HRM
            $url = "{$GLOBALS['base_url']}/hrm-dashboard";
            break;

        case 6: // Accounts
            $url = "{$GLOBALS['base_url']}/accounts-dashboard";
            break;


        case 7: // Client Cash In
            $url = "{$GLOBALS['base_url']}/ced-dashboard";
            break;
        case 8: // Accounts
            $url = "{$GLOBALS['base_url']}/my-dashboard";
            break;
        case 9: // sales
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
        case 20: // Purchase team lead
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

function getFollowUpCount(int $userId, string $type = 'today'): int
{
    $userId = (int) $userId;
    $db = $GLOBALS['db'];
    if ($type === 'today') {
        // Only today
        $sql = "
            SELECT COUNT(*) AS total
            FROM lead_follow_ups
            WHERE deleted_at IS NULL
              AND assigned_with = ?
              AND DATE(next_follow_up_date) = CURDATE()
        ";
    } elseif ($type === 'upcoming') {
        // Next 3 days
        $sql = "
            SELECT COUNT(*) AS total
            FROM lead_follow_ups
            WHERE deleted_at IS NULL
              AND assigned_with = ?
              AND DATE(next_follow_up_date) > CURDATE()
              AND DATE(next_follow_up_date) <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
        ";
    } else {
        return 0;
    }

    $stmt = $db->prepare($sql);
    if (!$stmt) return 0;

    $stmt->bind_param("i", $userId);
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
        'full_name'    => trim($postData['full_name'] ?? ''),
        'user_name'    => trim($postData['user_name'] ?? ($postData['username'] ?? '')),
        'phone'        => trim($postData['phone'] ?? ''),
        'email'        => trim($postData['email'] ?? ''),
        'designation'  => trim($postData['designation'] ?? ''),
        'address'      => trim($postData['address'] ?? ''),
        'nid'          => trim($postData['nid'] ?? ''),
        'role_id'      => intval($postData['role_id'] ?? ($isFromEmployee ? 2 : 0)),
        'password'     => trim($postData['password'] ?? ''),
        'media_link1'  => trim($postData['media_link1'] ?? ($postData['social_media1'] ?? '')),
        'media_link2'  => trim($postData['media_link2'] ?? ($postData['social_media2'] ?? '')),
    ];
    return $data;
}

function getUserProfileImage($user)
{
    $default = $GLOBALS['base_url'] . '/public/assets/images/avatar/avatar.png';

    if (!empty($user['employee_id']) && !empty($user['employee_photo'])) {
        $img = getImage('employees', $user['employee_photo']);
        return $img ?: $default;
    }

    if (!empty($user['photo'])) {
        $img = getImage('users', $user['photo']);
        return $img ?: $default;
    }

    return $default;
}



function getLoggedInUserImage()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $default = $GLOBALS['base_url'] . '/public/assets/images/avatar/avatar.png';

    $userId = $_SESSION['user']['id'] ?? null;
    if (!$userId) {
        return $default;
    }

    $db = $GLOBALS['db'];

    // Fetch user + employee photo
    $stmt = $db->prepare("
        SELECT 
            u.photo AS user_photo,
            e.photo AS employee_photo,
            e.id AS employee_id
        FROM users u
        LEFT JOIN employees e ON e.user_id = u.id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    // Priority: employee photo first, then user photo, then default
    if (!empty($row['employee_photo'])) {
        return getImage('employees', $row['employee_photo']);
    } elseif (!empty($row['user_photo'])) {
        return getImage('users', $row['user_photo']);
    }

    return $default;
}

// sweet alert 

function setSuccess($message)
{
    $_SESSION['flash_success'] = $message;
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
 * @return string
 */

function generateUniqueSlug($db, $name, $table = 'lead_sources', $column = 'slug')
{
    $name = trim($name);

    // Split words by space or punctuation (Unicode aware)
    $words = preg_split('/[\s\p{P}]+/u', $name, -1, PREG_SPLIT_NO_EMPTY);

    // Join words with dash
    $slug = implode('-', $words);

    // Lowercase English letters only
    $slug = mb_strtolower($slug, 'UTF-8');

    $originalSlug = $slug;
    $i = 1;

    while (slugExists($db, $slug, $table, $column)) {
        $slug = $originalSlug . '-' . $i;
        $i++;
    }

    return $slug;
}



function addCameraWithHls($db, $project_id, $project_name, $camera_name, $rtsp_url, $status = 'active', $hlsBase = __DIR__ . '/hls', $ffmpegPath = 'C:/ffmpeg/bin/ffmpeg.exe')
{
    // 1 Generate slugs
    $project_slug = strtolower(trim(preg_replace('/[^\w]+/', '_', $project_name)));
    $camera_slug  = strtolower(trim(preg_replace('/[^\w]+/', '_', $camera_name)));

    // 2 Insert camera into DB WITHOUT hls_path
    $stmt = $db->prepare("INSERT INTO cameras (project_id, camera_name, camera_slug, rtsp_url, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $project_id, $camera_name, $camera_slug, $rtsp_url, $status);
    $stmt->execute();

    // mysqli এর জন্য last inserted ID
    $camera_id = $db->insert_id;

    // 3 Prepare HLS folder
    $projectDir = rtrim($hlsBase, '/') . '/' . $project_slug;
    if (!is_dir($projectDir)) mkdir($projectDir, 0777, true);

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
function slugExists($db, $slug, $table = 'lead_sources', $column = 'slug')
{
    $stmt = $db->prepare("SELECT id FROM $table WHERE $column = ?");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result && $result->num_rows > 0;
}


function formatUrl($url)
{
    if (!$url) return '#';
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
        $stmt = $db->prepare("SELECT * FROM site_settings WHERE id = 1 LIMIT 1");
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
    if (empty($content)) {
        return '1 min read';
    }

    $text = trim(strip_tags((string) $content));
    $wordCount = preg_match_all('/\p{Bengali}+/u', $text);
    $minutes = max(1, ceil($wordCount / $wpm));

    return $minutes . ' min read';
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
        if ($amount === null || $amount === '') {
            return '';
        }

        $amount = round((float)$amount);

        if (!class_exists('NumberFormatter')) {
            return '';
        }

        $formatter = new NumberFormatter("en", NumberFormatter::SPELLOUT);
        $words = ucwords($formatter->format($amount));

        return $words . " Taka Only";
    }
}

// truncate text to N characters
function truncateText($text, $limit = 50)
{
    $text = strip_tags($text); // remove HTML
    if (strlen($text) > $limit) {
        return substr($text, 0, $limit) . '...';
    }
    return $text;
}


function formatDate($date)
{
    if (!$date || $date === '0000-00-00' || strtotime($date) === false) {
        return ''; // or "No Schedule" directly
    }
    return date('d F, Y', strtotime($date));
}

// formant time
function formatPunchTime($time)
{
    if (!$time || $time == '0000-00-00 00:00:00') {
        return '-';
    }
    return date('H:i', strtotime($time));
}


// for bd taka decimal format

function format_bdt($number)
{
    // Step 1: round to 2 decimal places
    $number = round((float)$number, 2);

    // Step 2: split integer & decimal
    $parts = explode('.', number_format($number, 2, '.', ''));
    $integer = $parts[0];
    $decimal = '.' . $parts[1];

    // Step 3: BDT comma formatting
    $last3 = substr($integer, -3);
    $rest  = substr($integer, 0, -3);

    if ($rest != '') {
        $rest = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $rest);
        return $rest . ',' . $last3 . $decimal;
    }

    return $last3 . $decimal;
}

// video url create 
function renderReviewVideo($url, $width = '100%', $height = '315')
{
    if (!$url) return '';

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
    return htmlspecialchars((string)($value ?? $default), ENT_QUOTES, 'UTF-8');
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
        // echo "Helper called"; die;
        // require_once 'path/to/MailService.php'; 
        $mail = new MailService();
        return $mail->sendMail($email, $title, $link, $assignedBy);
    }
}

function generateVerificationCode($length = 6)
{
    return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

// upload webp image
function uploadProjectImage($inputName, $quality = 75, $maxWidth = 1000)
{
    // 1. Check file
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmpPath = $_FILES[$inputName]['tmp_name'];

    // 2. Folder (project specific)
    $uploadDir = dirname(__DIR__, 2) . "/public/uploads/projects/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // 3. File name (always webp)
    $fileName = uniqid('project_') . '.webp';
    $destination = $uploadDir . $fileName;

    // 4. Get image info
    $imageInfo = getimagesize($tmpPath);
    if (!$imageInfo) return null;

    $mime = $imageInfo['mime'];

    // 5. Create source image
    switch ($mime) {
        case 'image/jpeg':
            $src = imagecreatefromjpeg($tmpPath);
            break;

        case 'image/png':
            $src = imagecreatefrompng($tmpPath);
            imagepalettetotruecolor($src);
            imagealphablending($src, true);
            imagesavealpha($src, true);
            break;

        case 'image/gif':
            $src = imagecreatefromgif($tmpPath);
            break;

        case 'image/webp':
            $src = imagecreatefromwebp($tmpPath);
            break;

        default:
            return null;
    }

    if (!$src) return null;

    // 6. Resize (keep ratio)
    $width  = imagesx($src);
    $height = imagesy($src);

    // যদি image already ছোট হয় → resize না
    if ($width > $maxWidth) {
        $newWidth  = $maxWidth;
        $newHeight = ($height / $width) * $newWidth;

        $dst = imagecreatetruecolor($newWidth, $newHeight);

        // transparency support
        imagealphablending($dst, false);
        imagesavealpha($dst, true);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    } else {
        $dst = $src; // no resize
    }

    // 7. Convert to WebP
    imagewebp($dst, $destination, $quality);

    // 8. Clean memory
    imagedestroy($src);
    if ($dst !== $src) {
        imagedestroy($dst);
    }

    return $fileName;
}
