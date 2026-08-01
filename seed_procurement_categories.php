<?php
$db = new mysqli("127.0.0.1", "root", "", "tphl_db");

$cats = [
    'IT & Electronics',
    'Furniture & Fixtures',
    'Machinery & Tools',
    'Office Stationery & Supplies',
    'Safety Equipment & Security',
    'Vehicle & Transport',
    'General Office Assets'
];

foreach ($cats as $cat) {
    $stmt = $db->prepare("INSERT IGNORE INTO office_procurement_categories (name) VALUES (?)");
    $stmt->bind_param("s", $cat);
    $stmt->execute();
}
echo "✓ Default Procurement Categories Seeded Successfully!\n";
