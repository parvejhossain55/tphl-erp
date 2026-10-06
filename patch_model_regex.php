<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\models\FlatModel.php';
$content = file_get_contents($file);

// create() method
$content = preg_replace(
    "/registration_money\s*\)\s*VALUES\s*\(\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?\)/",
    "registration_money,\n        ownership_type\n    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
    $content
);
$content = preg_replace(
    "/\\\$registration_money\s*=\s*\\\$data\['registration_money'\]\s*\?\?\s*null;\s*\\\$stmt->bind_param\(\s*\"isdiiiiiissississsisissdd\",/",
    "\$registration_money = \$data['registration_money'] ?? null; \$ownership_type = \$data['ownership_type'] ?? null;\n        \$stmt->bind_param(\n            \"isdiiiiiissississsisissdds\",",
    $content
);
$content = preg_replace(
    "/\\\$registration_money\s*\);/",
    "\$registration_money,\n            \$ownership_type\n        );",
    $content
);

// createFromCsvBulkSuite() method
$content = preg_replace(
    "/registration_money\s*\)\s*VALUES\s*\(\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?,\?\)/",
    "registration_money,\n                ownership_type\n            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
    $content
);
$content = preg_replace(
    "/\\\$registration_money\s*=\s*\\\$data\['registration_money'\]\s*\?\?\s*null;\s*\\\$stmt->bind_param\(\s*\"isdiiiiiissississsisissddd\",/",
    "\$registration_money = \$data['registration_money'] ?? null; \$ownership_type = \$data['ownership_type'] ?? null;\n        \$stmt->bind_param(\n            \"isdiiiiiissississsisissddds\",",
    $content
);

// update() method
$content = preg_replace(
    "/registration_money\s*=\s*\?\s*WHERE\s*id\s*=\s*\?\";/",
    "registration_money = ?, ownership_type = ?\n        WHERE id = ?\";",
    $content
);
$content = preg_replace(
    "/\\\$registration_money\s*=\s*\\\$data\['registration_money'\]\s*\?\?\s*null;\s*\\\$stmt->bind_param\(\s*\"isdiiiiiissisdisiddsdddi\",/",
    "\$registration_money = \$data['registration_money'] ?? null; \$ownership_type = \$data['ownership_type'] ?? null;\n        \$stmt->bind_param(\n            \"isdiiiiiissisdisiddsdddsi\",",
    $content
);
$content = preg_replace(
    "/\\\$registration_money,\s*\\\$id\s*\);/",
    "\$registration_money,\n            \$ownership_type,\n            \$id\n        );",
    $content
);

file_put_contents($file, $content);
echo 'Regex patch applied';
