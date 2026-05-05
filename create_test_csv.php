<?php
$file = 'test_bangla_work_type.csv';
$csvData = "\xEF\xBB\xBFDesign_Type_Name,Work_Type_Name,Description\nArchitectural Design,আগুন শনাক্তকরণ সেন্সর,আগুন নেভানোর সিস্টেম\n";
file_put_contents($file, $csvData);
echo "File created: $file";
