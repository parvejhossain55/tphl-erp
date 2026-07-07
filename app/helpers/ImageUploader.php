<?php

class ImageUploader
{
    /**
     * Resizes/crops and uploads an image using GD library if available, with fallbacks.
     */
    public static function upload($input, $module = 'general', $mode = 'original', $width = 300, $height = 300)
    {
        if (!isset($_FILES[$input]) || $_FILES[$input]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES[$input]['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            return null;
        }

        $uploadPath = dirname(__DIR__, 2) . "/public/uploads/{$module}/";
        if (!is_dir($uploadPath)) {
            if (!mkdir($uploadPath, 0777, true)) {
                error_log("[uploadImage] Failed to create upload directory: " . $uploadPath);
            }
        }

        $fileName = uniqid($module . '_') . '.' . $ext;
        $target = $uploadPath . $fileName;

        if ($mode === 'original') {
            return move_uploaded_file($_FILES[$input]['tmp_name'], $target) ? $fileName : null;
        }

        if (!extension_loaded('gd')) {
            return move_uploaded_file($_FILES[$input]['tmp_name'], $target) ? $fileName : null;
        }

        $src = null;
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $src = @imagecreatefromjpeg($_FILES[$input]['tmp_name']);
                break;
            case 'png':
                $src = @imagecreatefrompng($_FILES[$input]['tmp_name']);
                break;
            case 'gif':
                $src = @imagecreatefromgif($_FILES[$input]['tmp_name']);
                break;
            case 'webp':
                if (function_exists('imagecreatefromwebp')) {
                    $src = @imagecreatefromwebp($_FILES[$input]['tmp_name']);
                } else {
                    return move_uploaded_file($_FILES[$input]['tmp_name'], $target) ? $fileName : null;
                }
                break;
            default:
                return null;
        }

        if (!$src) {
            error_log("[uploadImage] Failed to create source image using GD. Falling back to original move_uploaded_file.");
            return move_uploaded_file($_FILES[$input]['tmp_name'], $target) ? $fileName : null;
        }

        $dst = imagecreatetruecolor($width, $height);
        if (!$dst) {
            imagedestroy($src);
            return move_uploaded_file($_FILES[$input]['tmp_name'], $target) ? $fileName : null;
        }

        if (in_array($ext, ['png', 'gif', 'webp'])) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            if ($transparent !== false) {
                imagefilledrectangle($dst, 0, 0, $width, $height, $transparent);
            }
        }

        $imgSize = getimagesize($_FILES[$input]['tmp_name']);
        if ($imgSize) {
            [$w, $h] = $imgSize;
            $target_ratio = $width / $height;
            $src_ratio = $w / $h;
            
            if ($src_ratio > $target_ratio) {
                $crop_h = $h;
                $crop_w = (int)($h * $target_ratio);
                $src_x = (int)(($w - $crop_w) / 2);
                $src_y = 0;
            } else {
                $crop_w = $w;
                $crop_h = (int)($w / $target_ratio);
                $src_x = 0;
                $src_y = 0;
            }
            
            imagecopyresampled($dst, $src, 0, 0, $src_x, $src_y, $width, $height, $crop_w, $crop_h);
        } else {
            imagedestroy($src);
            imagedestroy($dst);
            return move_uploaded_file($_FILES[$input]['tmp_name'], $target) ? $fileName : null;
        }

        $saved = false;
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $saved = @imagejpeg($dst, $target, 90);
                break;
            case 'png':
                $saved = @imagepng($dst, $target);
                break;
            case 'gif':
                $saved = @imagegif($dst, $target);
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    $saved = @imagewebp($dst, $target, 90);
                }
                break;
        }

        imagedestroy($src);
        imagedestroy($dst);

        if (!$saved) {
            error_log("[uploadImage] GD saving failed. Falling back to original move_uploaded_file.");
            return move_uploaded_file($_FILES[$input]['tmp_name'], $target) ? $fileName : null;
        }

        return $fileName;
    }

    /**
     * Gets a resource image URL, with default avatar fallback.
     */
    public static function get($module, $filename)
    {
        $default = $GLOBALS['base_url'] . '/public/assets/images/avatar/avatar.png';
        if (!$filename) {
            return $default;
        }

        $filePath = dirname(__DIR__, 2) . "/public/uploads/{$module}/{$filename}";
        if (!file_exists($filePath)) {
            return $default;
        }

        return $GLOBALS['base_url'] . "/public/uploads/{$module}/{$filename}";
    }

    /**
     * Gets a project image URL with default placeholder fallback.
     */
    public static function getProject($filename)
    {
        $placeholder = $GLOBALS['base_url'] . '/public/assets/images/project-placeholder.png';
        if (!$filename) {
            return ['url' => $placeholder, 'missing' => true];
        }

        $filePath = dirname(__DIR__, 2) . "/public/uploads/projects/{$filename}";
        if (!file_exists($filePath)) {
            return ['url' => $placeholder, 'missing' => true];
        }

        return ['url' => $GLOBALS['base_url'] . "/public/uploads/projects/{$filename}", 'missing' => false];
    }

    /**
     * General file upload handler.
     */
    public static function uploadFile($input, $module = 'general')
    {
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
     * Gets file URL.
     */
    public static function getFileUrl($module, $filename)
    {
        if (!$filename) {
            return null;
        }
        return $GLOBALS['base_url'] . "/public/uploads/{$module}/{$filename}";
    }

    /**
     * Uploads project image, resizing and converting to WebP.
     */
    public static function uploadProject($inputName, $quality = 75, $maxWidth = 1000)
    {
        if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmpPath = $_FILES[$inputName]['tmp_name'];
        $uploadDir = dirname(__DIR__, 2) . "/public/uploads/projects/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid('project_') . '.webp';
        $destination = $uploadDir . $fileName;

        $imageInfo = getimagesize($tmpPath);
        if (!$imageInfo) return null;

        $mime = $imageInfo['mime'];

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

        $width  = imagesx($src);
        $height = imagesy($src);

        if ($width > $maxWidth) {
            $newWidth  = $maxWidth;
            $newHeight = ($height / $width) * $newWidth;
            $dst = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        } else {
            $dst = $src;
        }

        imagewebp($dst, $destination, $quality);

        imagedestroy($src);
        if ($dst !== $src) {
            imagedestroy($dst);
        }

        return $fileName;
    }

    /**
     * Gets user profile image path.
     */
    public static function getUserProfile($user)
    {
        $default = $GLOBALS['base_url'] . '/public/assets/images/avatar/avatar.png';

        if (!empty($user['employee_id']) && !empty($user['employee_photo'])) {
            $img = self::get('employees', $user['employee_photo']);
            return $img ?: $default;
        }

        if (!empty($user['photo'])) {
            $img = self::get('users', $user['photo']);
            return $img ?: $default;
        }

        return $default;
    }

    /**
     * Gets currently logged-in user image path.
     */
    public static function getLoggedInUser()
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

        if (!empty($row['employee_photo'])) {
            return self::get('employees', $row['employee_photo']);
        } elseif (!empty($row['user_photo'])) {
            return self::get('users', $row['user_photo']);
        }

        return $default;
    }
}
