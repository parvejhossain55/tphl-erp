<?php
$content = file_get_contents('scratch/test_weekly_out.json');
$content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
$json = json_decode($content, true);
if ($json && isset($json['data'])) {
    foreach ($json['data'] as $m) {
        echo "Month: " . $m['month_label'] . "\n";
        foreach ($m['weeks'] as $w) {
            if (count($w['targets']) > 0 || count($w['dprs']) > 0) {
                echo "  Week: " . $w['week_num'] . "\n";
                echo "    Targets: " . count($w['targets']) . "\n";
                echo "    DPRs: " . count($w['dprs']) . "\n";
            }
        }
    }
} else {
    echo "Failed to decode json.\n";
}
