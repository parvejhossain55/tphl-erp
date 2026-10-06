<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\models\FlatModel.php';
$content = file_get_contents($file);

// create() method
$content = str_replace(
    "booking_money,\n        registration_money\n    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
    "booking_money,\n        registration_money,\n        ownership_type\n    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
    $content
);
$content = str_replace(
    "\$registration_money = \$data['registration_money'] ?? null;\n        \$stmt->bind_param(\n            \"isdiiiiiissississsisissdd\",",
    "\$registration_money = \$data['registration_money'] ?? null; \$ownership_type = \$data['ownership_type'] ?? null;\n        \$stmt->bind_param(\n            \"isdiiiiiissississsisissdds\",",
    $content
);
$content = str_replace(
    "\$booking_money,\n            \$registration_money\n        );",
    "\$booking_money,\n            \$registration_money,\n            \$ownership_type\n        );",
    $content
);

// createFromCsvBulkSuite() method
$content = str_replace(
    "booking_money,\n                registration_money\n            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
    "booking_money,\n                registration_money,\n                ownership_type\n            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
    $content
);
$content = str_replace(
    "\$registration_money = \$data['registration_money'] ?? null;\n        \$stmt->bind_param(\n            \"isdiiiiiissississsisissddd\",",
    "\$registration_money = \$data['registration_money'] ?? null; \$ownership_type = \$data['ownership_type'] ?? null;\n        \$stmt->bind_param(\n            \"isdiiiiiissississsisissddds\",",
    $content
);
$content = str_replace(
    "\$booking_money,\n            \$registration_money\n        );",
    "\$booking_money,\n            \$registration_money,\n            \$ownership_type\n        );",
    $content
);

// update() method
$content = str_replace(
    "suite_id = ?, per_sqft_price = ?, booking_money = ?, registration_money = ?\n        WHERE id = ?\";",
    "suite_id = ?, per_sqft_price = ?, booking_money = ?, registration_money = ?, ownership_type = ?\n        WHERE id = ?\";",
    $content
);
$content = str_replace(
    "\$registration_money = \$data['registration_money'] ?? null;\n        \$stmt->bind_param(\n            \"isdiiiiiissisdisiddsdddi\",",
    "\$registration_money = \$data['registration_money'] ?? null; \$ownership_type = \$data['ownership_type'] ?? null;\n        \$stmt->bind_param(\n            \"isdiiiiiissisdisiddsdddsi\",",
    $content
);
$content = str_replace(
    "\$booking_money,\n            \$registration_money,\n            \$id\n        );",
    "\$booking_money,\n            \$registration_money,\n            \$ownership_type,\n            \$id\n        );",
    $content
);

file_put_contents($file, $content);
echo 'Replaced successfully';
