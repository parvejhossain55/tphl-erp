<?php
$content = file_get_contents(__DIR__ . '/app/models/AssetManagement.php');
preg_match_all('/public\s+function\s+([a-zA-Z0-9_]+)/', $content, $matches);
print_r($matches[1]);
