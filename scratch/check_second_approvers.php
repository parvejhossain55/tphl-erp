<?php
$data = json_decode(file_get_contents(__DIR__ . '/pending_results.json'), true);
$secondApprovers = [];
foreach ($data as $d) {
    $sec = trim($d['second_approver']) ?: 'Not Set';
    $secondApprovers[$sec] = ($secondApprovers[$sec] ?? 0) + 1;
}
print_r($secondApprovers);
