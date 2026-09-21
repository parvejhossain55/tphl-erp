<?php
$data = json_decode(file_get_contents(__DIR__ . '/pending_results.json'), true);
$approvers = [];
$byType = [];
$levels = [];

foreach ($data as $d) {
    $byType[$d['category']] = ($byType[$d['category']] ?? 0) + 1;
    $levels[$d['pending_level']] = ($levels[$d['pending_level']] ?? 0) + 1;
    $app = trim($d['currently_pending_with']) ?: 'Unknown / Not Set';
    if (!isset($approvers[$app])) {
        $approvers[$app] = ['1st' => 0, '2nd' => 0, 'types' => []];
    }
    if ($d['pending_level'] === '1st Approval Pending') {
        $approvers[$app]['1st']++;
    } else {
        $approvers[$app]['2nd']++;
    }
    $approvers[$app]['types'][$d['category']] = ($approvers[$app]['types'][$d['category']] ?? 0) + 1;
}

echo "Total Pending: " . count($data) . PHP_EOL;
print_r($byType);
print_r($levels);

uasort($approvers, function($a, $b) {
    return ($b['1st'] + $b['2nd']) <=> ($a['1st'] + $a['2nd']);
});

foreach ($approvers as $name => $info) {
    echo str_pad($name, 45) . " | Total: " . str_pad($info['1st'] + $info['2nd'], 2) . " | 1st: " . str_pad($info['1st'], 2) . " | 2nd: " . str_pad($info['2nd'], 2) . " | " . json_encode($info['types']) . PHP_EOL;
}
