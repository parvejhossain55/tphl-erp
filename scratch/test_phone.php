<?php
require_once 'app/config/config.php';
$db = new mysqli("127.0.0.1", "root", "", "tphl_db");

$clientPhone = "8801910-703190"; // Note: this is exactly what the user provided, it might contain a special dash
$phone = preg_replace('/[^0-9]/', '', (string)$clientPhone);
$shortPhone = $phone;
if (str_starts_with($shortPhone, '880')) $shortPhone = substr($shortPhone, 3);
if (str_starts_with($shortPhone, '0')) $shortPhone = substr($shortPhone, 1);
if (strlen($shortPhone) > 10) $shortPhone = substr($shortPhone, -10);
$phoneLike = '%' . $shortPhone;

echo "phoneLike: $phoneLike\n";

$stmt = $db->prepare("
    SELECT id, full_name, phone, phone_number 
    FROM leads 
    WHERE deleted_at IS NULL 
      AND (
          REPLACE(REPLACE(REPLACE(COALESCE(phone,''), ' ', ''), '-', ''), '+', '') LIKE ?
          OR REPLACE(REPLACE(REPLACE(COALESCE(phone_number,''), ' ', ''), '-', ''), '+', '') LIKE ?
      )
    LIMIT 1
");
$stmt->bind_param("ss", $phoneLike, $phoneLike);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
var_dump($res);

// Also try the same for resolveBookingByProjectAndFlat
$stmt2 = $db->prepare("
    SELECT id, phone, phone_number 
    FROM leads 
    WHERE deleted_at IS NULL 
      AND (
          REPLACE(REPLACE(REPLACE(COALESCE(phone,''), ' ', ''), '-', ''), '+', '') LIKE ?
          OR REPLACE(REPLACE(REPLACE(COALESCE(phone_number,''), ' ', ''), '-', ''), '+', '') LIKE ?
      )
");
$stmt2->bind_param("ss", $phoneLike, $phoneLike);
$stmt2->execute();
var_dump($stmt2->get_result()->fetch_all(MYSQLI_ASSOC));
