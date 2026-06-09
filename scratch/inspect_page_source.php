<?php
// Set up session dummy to fetch page as admin or appropriate role if needed
// For now, let's just do a simple curl/file_get_contents. 
// If it requires login, we can pass session cookie.
// Let's first try to print the page content lines.

$html = file_get_contents("http://localhost:8080/TPHL-ERP/con-land-acquisitions");
if ($html === false) {
    echo "Failed to fetch page. Maybe login is required.\n";
    // Let's simulate a login session or read cookies
    exit;
}

$lines = explode("\n", $html);
echo "Total lines: " . count($lines) . "\n";
$start = max(0, 1317 - 20);
$end = min(count($lines), 1317 + 20);

for ($i = $start; $i < $end; $i++) {
    echo ($i + 1) . ": " . $lines[$i] . "\n";
}
