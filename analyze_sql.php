<?php
$target_file = __DIR__ . '/schema/custom-migration/tphl_db.sql';

if (!file_exists($target_file)) {
    die("Target file not found: $target_file");
}

function extract_schema($file_path) {
    $content = file_get_contents($file_path);
    $tables = [];
    $alters = [];
    $inserts = [];

    // Match CREATE TABLE `table_name`
    preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i', $content, $matches);
    if (!empty($matches[1])) {
        $tables = array_unique($matches[1]);
    }

    // Match ALTER TABLE `table_name`
    preg_match_all('/ALTER\s+TABLE\s+`?([a-zA-Z0-9_]+)`?/i', $content, $matches);
    if (!empty($matches[1])) {
        $alters = array_unique($matches[1]);
    }
    
    // Extract columns from CREATE TABLE
    $table_cols = [];
    preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?\s*\((.*?)\)\s*(ENGINE|DEFAULT|;)/is', $content, $create_matches, PREG_SET_ORDER);
    foreach ($create_matches as $m) {
        $tname = $m[1];
        $cols_block = $m[2];
        preg_match_all('/`([a-zA-Z0-9_]+)`/', $cols_block, $col_matches);
        if (!isset($table_cols[$tname])) $table_cols[$tname] = [];
        $table_cols[$tname] = array_unique(array_merge($table_cols[$tname], $col_matches[1]));
    }
    
    // Match ALTER TABLE Add Column
    preg_match_all('/ALTER\s+TABLE\s+`?([a-zA-Z0-9_]+)`?\s+ADD\s+(?:COLUMN\s+)?`?([a-zA-Z0-9_]+)`?/i', $content, $alter_add_matches, PREG_SET_ORDER);
    foreach ($alter_add_matches as $m) {
        $tname = $m[1];
        $colname = $m[2];
        if (!isset($table_cols[$tname])) $table_cols[$tname] = [];
        $table_cols[$tname][] = $colname;
    }

    return [
        'tables' => $tables,
        'alters' => $alters,
        'columns' => $table_cols
    ];
}

$main_schema = extract_schema($target_file);
// echo "Main schema has " . count($main_schema['tables']) . " tables.\n";

$other_sql_files = [];
$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));
foreach ($iter as $file) {
    if ($file->getExtension() === 'sql') {
        $path = $file->getPathname();
        // Ignore the target file and any dumps inside vendor or similar
        if (strpos($path, 'vendor') !== false) continue;
        if (strpos($path, 'tphl_db2.sql') !== false) continue; // ignore tphl_db2 as it's huge maybe
        if ($path !== $target_file) {
            $other_sql_files[] = $path;
        }
    }
}

$missing_tables = [];
$missing_columns = [];
$missing_sql_statements = [];

foreach ($other_sql_files as $f) {
    $schema = extract_schema($f);
    $content = file_get_contents($f);
    
    foreach ($schema['tables'] as $t) {
        if (!in_array($t, $main_schema['tables'])) {
            $missing_tables[$t] = $f;
            // Extract the statement
            preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?'.$t.'`?.*?;/is', $content, $m);
            if (!empty($m[0])) $missing_sql_statements[] = "-- From: " . basename($f) . "\n" . $m[0];
        }
    }
    
    foreach ($schema['columns'] as $t => $cols) {
        if (isset($main_schema['columns'][$t])) {
            foreach ($cols as $c) {
                if (!in_array($c, $main_schema['columns'][$t])) {
                    // It's not a primary key or index name usually, but let's just log it
                    // To avoid false positives, we check if it was an ADD COLUMN
                    preg_match('/ALTER\s+TABLE\s+`?'.$t.'`?\s+ADD\s+(?:COLUMN\s+)?`?'.$c.'`?.*?;/is', $content, $m);
                    if (!empty($m[0])) {
                        $missing_columns[$t][] = $c;
                        $missing_sql_statements[] = "-- From: " . basename($f) . "\n" . $m[0];
                    }
                }
            }
        } else {
             // table entirely missing, handled above
        }
    }
}

echo "=== Missing Elements ===\n";
if (empty($missing_sql_statements)) {
    echo "No missing SQL found from other files.\n";
} else {
    echo implode("\n\n", array_unique($missing_sql_statements)) . "\n";
}
