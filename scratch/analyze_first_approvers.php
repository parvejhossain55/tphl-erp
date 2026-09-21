<?php
$data = json_decode(file_get_contents(__DIR__ . '/pending_results.json'), true);

$firstAppDept = [];
foreach ($data as $d) {
    if ($d['pending_level'] === '1st Approval Pending') {
        $app = trim($d['currently_pending_with']) ?: 'Unknown';
        if (!isset($firstAppDept[$app])) {
            $firstAppDept[$app] = ['count' => 0, 'depts' => [], 'employees' => []];
        }
        $firstAppDept[$app]['count']++;
        $firstAppDept[$app]['depts'][$d['department']] = ($firstAppDept[$app]['depts'][$d['department']] ?? 0) + 1;
        $firstAppDept[$app]['employees'][$d['employee_name']] = ($firstAppDept[$app]['employees'][$d['employee_name']] ?? 0) + 1;
    }
}

uasort($firstAppDept, function($a, $b) {
    return $b['count'] <=> $a['count'];
});

foreach ($firstAppDept as $name => $info) {
    echo "• $name: {$info['count']} requests\n";
    echo "  Depts: " . implode(', ', array_keys($info['depts'])) . "\n";
    echo "  Employees: " . implode(', ', array_keys($info['employees'])) . "\n\n";
}
