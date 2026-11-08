

<?php
// Controller থেকে পাঠানো variable
$current_route = !empty($current_route) ? $current_route : ($data['current_route'] ?? ($current_page ?? ''));

if (!function_exists('isActive')) {
    function isActive($pages, $current_route)
    {
        if (is_array($pages)) {
            foreach ($pages as $page) {
                if ($current_route === $page || ($page !== '' && strpos($current_route, $page) === 0)) {
                    return 'active';
                }
            }
            return '';
        }
        return ($current_route === $pages || ($pages !== '' && strpos($current_route, $pages) === 0)) ? 'active' : '';
    }
}

if (!function_exists('isMenuOpen')) {
    function isMenuOpen($pages, $current_route)
    {
        if (is_array($pages)) {
            return in_array($current_route, $pages, true);
        }
        return ($current_route === $pages);
    }
}
if (!function_exists('isMenuOpenAlways')) {
    function isMenuOpenAlways($pages, $current_route) {
        // submenu always display block
        return true;
    }
}


if (!isset($page_title)) $page_title = "Blank";

require __DIR__ . "/header.php";

if (!empty($show_breadcrumb) && $show_breadcrumb === true) {
    require __DIR__ . "/breadcrumb-area.php";
}
?>

<?php
// Fetch site settings for global use if not already fetched
global $db;
if (!isset($siteSettings) && isset($db)) {
    $siteSettings = $db->query("SELECT * FROM site_settings LIMIT 1")->fetch_assoc();
}
// Include Universal Print Pad
require __DIR__ . "/print-pad.php";
?>

<div class="app__slide-wrapper">
    <?= $content ?>
</div>

<?php require __DIR__ . "/footer.php"; ?>

<?php if (!empty($page_script)): ?>
    <script>
        <?= $page_script ?>
    </script>
<?php endif; ?>

<?php ?>
<script>
 // Ensure base_url is globally available (avoids conflict with const base_url in view scripts)
 window.base_url = window.base_url || "<?= $GLOBALS['base_url'] ?>"; 
</script>