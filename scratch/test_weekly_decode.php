<?php
$content = file_get_contents('scratch/test_weekly_out.json');
$content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
$json = json_decode($content, true);
echo json_encode($json, JSON_PRETTY_PRINT);
