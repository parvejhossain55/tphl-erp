<?php

$db = new mysqli("127.0.0.1", "root", "", "tphl_db");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// 1. Get flat 132
$res = $db->query("SELECT * FROM flats WHERE id = 132");
$flat = $res->fetch_assoc();
echo "Before: land_share_value = " . var_export($flat['land_share_value'], true) . "\n";

// 2. Mock data for update
$data = [
    'project_id'             => 5,
    'flat_name'              => '4-D',
    'sqft'                   => 1200,
    'bhk'                    => 3,
    'bedroom'                => 3,
    'bathroom'               => 2,
    'balcony'                => 2,
    'drawing_dining_kitchen' => 1,
    'common_dining'          => 1,
    'floor_no'               => 4,
    'facing_site'            => 'North',
    'is_shop'                => 0,
    'unit_type'              => 'residential',
    'price_value'            => '5000000',
    'shop_service_charge'    => null,
    'facilities'             => 'Parking',
    'created_by'             => 1,
    'land_share_value'       => '300000',
    'development_charges'    => null,
];

$sql = "UPDATE flats SET
          project_id = ?, flat_name = ?, sqft = ?, bhk = ?, bedroom = ?, bathroom = ?, balcony = ?,
          drawing_dining_kitchen = ?, common_dining = ?, floor_no = ?, facing_site = ?,
          is_shop = ?, unit_type = ?,
          price_value = ?, shop_service_charge = ?,
          facilities = ?, created_by = ?, land_share_value = ?, development_charges = ?
          WHERE id = ?";

$stmt = $db->prepare($sql);
$id = 132;
$stmt->bind_param(
    "isiiiiiiiisisdisiddi",
    $data['project_id'],
    $data['flat_name'],
    $data['sqft'],
    $data['bhk'],
    $data['bedroom'],
    $data['bathroom'],
    $data['balcony'],
    $data['drawing_dining_kitchen'],
    $data['common_dining'],
    $data['floor_no'],
    $data['facing_site'],
    $data['is_shop'],
    $data['unit_type'],
    $data['price_value'],
    $data['shop_service_charge'],
    $data['facilities'],
    $data['created_by'],
    $data['land_share_value'],
    $data['development_charges'],
    $id
);


$stmt->execute();

$res = $db->query("SELECT * FROM flats WHERE id = 132");
$flat = $res->fetch_assoc();
echo "After: land_share_value = " . var_export($flat['land_share_value'], true) . "\n";
