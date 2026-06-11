<?php
// ======================================================
// TPHL-ERP Upload Diagnostic — DELETE AFTER USE
// ======================================================
if (!isset($_GET['key']) || $_GET['key'] !== 'tphl_diag_2024') {
    die('Unauthorized');
}

$uploadDir = __DIR__ . '/../public/uploads/projects/';
$altDir    = __DIR__ . '/uploads/projects/';

echo '<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5}
.ok{color:#198754;font-weight:700}.err{color:#dc3545;font-weight:700}
.warn{color:#f59e0b;font-weight:700}.card{background:#fff;padding:16px;
border-radius:8px;margin-bottom:12px;box-shadow:0 2px 6px rgba(0,0,0,.08)}</style>';

echo '<div class="card"><h2>🔍 TPHL Upload Diagnostic</h2>';

// 1. PHP version
echo '<p><b>PHP Version:</b> ' . phpversion() . '</p>';

// 2. GD support
if (extension_loaded('gd')) {
    $gdInfo = gd_info();
    $webp   = !empty($gdInfo['WebP Support']) ? '<span class="ok">✅ YES</span>' : '<span class="err">❌ NO</span>';
    echo '<p><b>GD Extension:</b> <span class="ok">✅ Loaded</span> | <b>WebP Support:</b> ' . $webp . '</p>';
} else {
    echo '<p><b>GD Extension:</b> <span class="err">❌ NOT loaded</span></p>';
}

// 3. Check upload dirs
foreach ([
    'Expected path (../public/uploads/projects/)' => $uploadDir,
    'Alt path (./uploads/projects/)'              => $altDir,
    '__DIR__ itself'                              => __DIR__,
] as $label => $dir) {
    $exists   = is_dir($dir);
    $writable = $exists && is_writable($dir);
    $status   = $exists ? ($writable ? '<span class="ok">✅ Exists & Writable</span>' : '<span class="warn">⚠️ Exists but NOT writable</span>') : '<span class="err">❌ Does NOT exist</span>';
    echo '<p><b>' . $label . ':</b><br><code>' . realpath($dir) . '</code><br>' . $status . '</p>';
}

// 4. Try to write a test file
$testFile = $uploadDir . 'diag_test_' . time() . '.txt';
$wrote = @file_put_contents($testFile, 'test');
if ($wrote !== false) {
    echo '<p><b>Write Test:</b> <span class="ok">✅ Successfully wrote test file</span></p>';
    @unlink($testFile);
} else {
    echo '<p><b>Write Test:</b> <span class="err">❌ FAILED to write to upload directory</span></p>';
}

// 5. List existing files
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    $files = array_filter($files, fn($f) => !in_array($f, ['.', '..']));
    echo '<p><b>Files in upload dir (' . count($files) . '):</b><br>';
    foreach (array_slice($files, -5) as $f) {
        echo '<code>' . htmlspecialchars($f) . '</code><br>';
    }
    echo '</p>';
}

// 6. Check helper path
$helperPath = dirname(__DIR__) . '/app/helpers/helper.php';
echo '<p><b>Helper file:</b> ' . (file_exists($helperPath) ? '<span class="ok">✅ Found</span>' : '<span class="err">❌ NOT found</span>') . ' at <code>' . $helperPath . '</code></p>';

// 7. Check getProjectImage function
if (file_exists($helperPath)) {
    require_once $helperPath;
    if (function_exists('getProjectImage')) {
        echo '<p><b>getProjectImage():</b> <span class="ok">✅ Function exists (new code deployed)</span></p>';
    } else {
        echo '<p><b>getProjectImage():</b> <span class="err">❌ Function NOT found (old code still running)</span></p>';
    }
}

echo '</div>';
echo '<p style="color:#888;font-size:12px">⚠️ Delete <code>public/diag.php</code> after use.</p>';
