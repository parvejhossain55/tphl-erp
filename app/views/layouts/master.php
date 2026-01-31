

<?php
// Controller থেকে পাঠানো variable
$current_route = $data['current_route'] ?? ($current_page ?? '');

function isActive($pages, $current_route)
{
    if (is_array($pages)) {
        return in_array($current_route, $pages, true) ? 'active' : '';
    }
    return ($current_route === $pages) ? 'active' : '';
}

function isMenuOpen($pages, $current_route)
{
    if (is_array($pages)) {
        return in_array($current_route, $pages, true);
    }
    return ($current_route === $pages);
}
function isMenuOpenAlways($pages, $current_route) {
    // submenu always display block
    return true;
}


if (!isset($page_title)) $page_title = "Blank";

require __DIR__ . "/header.php";

if (!empty($show_breadcrumb) && $show_breadcrumb === true) {
    require __DIR__ . "/breadcrumb-area.php";
}
?>

<div class="app__slide-wrapper">
    <?= $content ?>
</div>

<?php require __DIR__ . "/footer.php"; ?>


?>
<script>
 const base_url = "<?= $GLOBALS['base_url'] ?>"; 

</script>