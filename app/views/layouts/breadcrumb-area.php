<?php
// fallback values
if (!isset($page)) {
    $page = "Page";
}


?>

<div class="breadcrumb__area">
    <div class="breadcrumb__wrapper mb-25">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item fs-5"><a href="<?= $GLOBALS['base_url'] ?>/home">Home</a></li>
                <li class="breadcrumb-item active fs-5" aria-current="page">
                    <?php echo $page; ?>
                </li>
            </ol>
        </nav>
        
    </div>
</div>

