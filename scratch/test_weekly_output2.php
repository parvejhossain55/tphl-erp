<?php
$c = file_get_contents('scratch/test_weekly_out2.json');
$c = mb_convert_encoding($c, 'UTF-8', 'UTF-16LE');
$j = json_decode($c, true);
if(!$j) {
    $j = json_decode(file_get_contents('scratch/test_weekly_out2.json'), true);
}
if(!$j) {
    echo "Still no JSON: " . json_last_error_msg();
    exit;
}
foreach($j['data'] as $m){
    echo "M: ".$m['month_label']."\n";
    foreach($m['weeks'] as $w){
        echo " W:".$w['week_num']." T:".count($w['targets'])." D:".count($w['dprs'])."\n";
    }
}
