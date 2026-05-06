<?php
$file = 'migrate_tphl_db.sql';
$content = file_get_contents($file);

$tables = ['con_financial_transactions'];

foreach ($tables as $table) {
    echo "--- $table ---\n";
    $pattern = "/CREATE TABLE `$table`[\s\S]*?ENGINE=InnoDB.*?;/";
    if (preg_match($pattern, $content, $matches)) {
        echo $matches[0] . "\n\n";
    } else {
        echo "Not found.\n\n";
    }
}
?>
