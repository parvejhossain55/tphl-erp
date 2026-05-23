<?php
$f = 'app/views/phase2/purchase-requests/show-purchase-request.php';
$c = file_get_contents($f);
$replace = '<a href="<?= $base_url ?>/con-stocks" target="_blank" class="btn btn-info btn-sm text-white"><i class="fal fa-boxes me-1"></i> Show Stock</a>' . "\n        " . '<a href="<?= $base_url ?>/con-purchase-requests" class="btn btn-secondary btn-sm">';
$c = str_replace('<a href="<?= $base_url ?>/con-purchase-requests" class="btn btn-secondary btn-sm">', $replace, $c);
file_put_contents($f, $c);
echo "Done.";
