<?php
$data = json_decode(file_get_contents(__DIR__ . '/pending_results.json'), true);
echo "Total Pending: " . count($data) . PHP_EOL . PHP_EOL;

$approvers = [];
$byType = [];
$levels = [];

foreach ($data as $d) {
    $byType[$d['category']] = ($byType[$d['category']] ?? 0) + 1;
    $levels[$d['pending_level']] = ($levels[$d['pending_level']] ?? 0) + 1;
    $app = trim($d['currently_pending_with']) ?: 'Unknown / Not Set';
    if (!isset($approvers[$app])) {
        $approvers[$app] = ['1st' => 0, '2nd' => 0, 'types' => [], 'items' => []];
    }
    if ($d['pending_level'] === '1st Approval Pending') {
        $approvers[$app]['1st']++;
    } else {
        $approvers[$app]['2nd']++;
    }
    $approvers[$app]['types'][$d['category']] = ($approvers[$app]['types'][$d['category']] ?? 0) + 1;
    $approvers[$app]['items'][] = $d;
}

echo "=== BREAKDOWN BY CATEGORY ===" . PHP_EOL;
foreach ($byType as $type => $cnt) {
    echo "- $type: $cnt" . PHP_EOL;
}
echo PHP_EOL;

echo "=== BREAKDOWN BY APPROVAL STAGE ===" . PHP_EOL;
foreach ($levels as $level => $cnt) {
    echo "- $level: $cnt" . PHP_EOL;
}
echo PHP_EOL;

uasort($approvers, function($a, $b) {
    return ($b['1st'] + $b['2nd']) <=> ($a['1st'] + $a['2nd']);
});

echo "=== BREAKDOWN BY APPROVER ===" . PHP_EOL;
printf("%-48s | %-6s | %-6s | %-6s | %s\n", "Approver Name", "Total", "1st", "2nd", "Categories");
echo str_repeat("-", 95) . PHP_EOL;

foreach ($approvers as $name => $info) {
    $typeStr = [];
    foreach ($info['types'] as $t => $c) {
        $typeStr[] = "$t: $c";
    }
    printf("%-48s | %-6d | %-6d | %-6d | %s\n", $name, $info['1st'] + $info['2nd'], $info['1st'], $info['2nd'], implode(", ", $typeStr));
}

echo PHP_EOL . "=== DETAILED LIST PER APPROVER ===" . PHP_EOL;
foreach ($approvers as $name => $info) {
    echo "\n--------------------------------------------------------------------------------\n";
    echo "APPROVER: $name (Total: " . ($info['1st'] + $info['2nd']) . " | 1st: {$info['1st']} | 2nd: {$info['2nd']})\n";
    echo "--------------------------------------------------------------------------------\n";
    foreach ($info['items'] as $it) {
        $subCat = $it['type'];
        echo "  • [{$it['pending_level']}] [{$subCat}] {$it['employee_name']} (Dept: {$it['department']}, ID: {$it['employee_office_id']})\n";
        echo "    Period: {$it['period']} | Reason: {$it['reason']}\n";
        echo "    Approver 1: {$it['first_approver']} (Status: {$it['first_status']}) | Approver 2: {$it['second_approver']} (Status: {$it['second_status']})\n\n";
    }
}
