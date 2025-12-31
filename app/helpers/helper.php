<?php
function uploadImage(  $input,$module = 'general', $mode = 'original',  $width = 300,   $height = 300) {
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
        return $GLOBALS['base_url'] . "/uploads/no-image.png";
    }

    return $GLOBALS['base_url'] . "/uploads/{$module}/{$filename}";
}


function paginateLinksSecondary($route, $currentPage, $totalPages)
{
    // Make sure page numbers are integer
    $currentPage = intval($currentPage);
    $totalPages  = intval($totalPages);

    if ($totalPages <= 1) return ""; // pagination no need

    $html  = '<nav>';
    $html .= '<ul class="pagination pagination-secondary">';

    // Previous Button
    if ($currentPage > 1) {
        $html .= '<li class="page-item">
                    <a class="page-link" href="' . $route . '?page=' . ($currentPage - 1) . '">Previous</a>
                  </li>';
    } else {
        $html .= '<li class="page-item disabled">
                    <span class="page-link">Previous</span>
                  </li>';
    }

    // Page Numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = ($i == $currentPage) ? "active" : "";

        $html .= '<li class="page-item ' . $active . '">
                    <a class="page-link" href="' . $route . '?page=' . $i . '">' . $i . '</a>
                  </li>';
    }

    // Next Button
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item">
                    <a class="page-link" href="' . $route . '?page=' . ($currentPage + 1) . '">Next</a>
                  </li>';
    } else {
        $html .= '<li class="page-item disabled">
                    <span class="page-link">Next</span>
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

    if (!isset($_SESSION['user'])) {
     header("Location: " . $GLOBALS['base_url'] . "/admin-login");

        exit;
    }
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


function hasPermission($permissionName)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // session check
    if (!isset($_SESSION['user']['role_id'])) {
        return false; // role_id missing
    }

    $roleId = $_SESSION['user']['role_id'];

    // session থেকে permission array
    if (!isset($_SESSION['user']['permissions']) || !is_array($_SESSION['user']['permissions'])) {
        return false; // permissions missing
    }

    $permissions = array_map('strtolower', $_SESSION['user']['permissions']); // lowercase
    $permissionName = strtolower($permissionName);

    return in_array($permissionName, $permissions);
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
    // User created from employee
    if (!empty($user['employee_id']) && !empty($user['employee_photo'])) {
        return getImage('employees', $user['employee_photo']);
    }

    // Normal user
    if (!empty($user['photo'])) {
        return getImage('users', $user['photo']);
    }

    // Default avatar
    return getImage('users', 'default.png');
}


function getLoggedInUserImage()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $userId = $_SESSION['user']['id'] ?? null;
    if (!$userId) {
        return getImage('users', 'default.png');
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

    return getImage('users', 'default.png');
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
    // lowercase, trim, replace spaces/non-alphanum with dash
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($name)));
    $originalSlug = $slug;
    $i = 1;

    // loop until unique
    while (slugExists($db, $slug, $table, $column)) {
        $slug = $originalSlug . '-' . $i;
        $i++;
    }

    return $slug;
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


//rules
function getFlatRules($separator = "\n")
{
    static $rulesCache = null;

    if ($rulesCache === null) {
        $db = $GLOBALS['db']; 
        $rulesCache = [];
        $query = "SELECT rules_description FROM flat_purchase_rules WHERE status = 1 ORDER BY id ASC";
        $result = $db->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rulesCache[] = $row['rules_description'];
            }
        }
    }

    return implode($separator, $rulesCache);
}



// $readTime = calculateReadTime($_POST['content']);

