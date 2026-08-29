<?php
$json = file_get_contents('https://hrm.tipsoi.pro/inovace-client/v2/api-docs');
$data = json_decode($json, true);
if ($data && isset($data['paths'])) {
    foreach ($data['paths'] as $path => $methods) {
        if (strpos($path, 'pending-list') !== false || strpos($path, 'application') !== false) {
            echo $path . PHP_EOL;
        }
    }
} else {
    echo "v2/api-docs failed\n";
    $json = file_get_contents('https://hrm.tipsoi.pro/v3/api-docs');
    $data = json_decode($json, true);
    if ($data && isset($data['paths'])) {
        foreach ($data['paths'] as $path => $methods) {
            if (strpos($path, 'pending-list') !== false || strpos($path, 'application') !== false) {
                echo $path . PHP_EOL;
            }
        }
    } else {
        echo "v3/api-docs failed\n";
    }
}

