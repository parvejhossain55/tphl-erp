<?php
require 'app/config/config.php';
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
$db->query("UPDATE con_material_categories SET item_type = 'Structural', pr_to_qtn_days = 5, cs_analysis_days = 2, approval_days = 10, wo_po_days = 1, adv_payment_days = 5, transport_days = 7, total_lead_days = 30 WHERE name IN ('Deform Bar', 'Steel')");
$db->query("UPDATE con_material_categories SET item_type = 'Structural', pr_to_qtn_days = 5, cs_analysis_days = 2, approval_days = 10, wo_po_days = 1, adv_payment_days = 5, transport_days = 7, total_lead_days = 30 WHERE name = 'Cement'");
$db->query("UPDATE con_material_categories SET item_type = 'Structural', pr_to_qtn_days = 5, cs_analysis_days = 2, approval_days = 10, wo_po_days = 1, adv_payment_days = 5, transport_days = 7, total_lead_days = 30 WHERE name IN ('Stone Chips', 'Stone')");
$db->query("UPDATE con_material_categories SET item_type = 'Structural', pr_to_qtn_days = 5, cs_analysis_days = 2, approval_days = 10, wo_po_days = 1, adv_payment_days = 5, transport_days = 7, total_lead_days = 30 WHERE name = 'Sand'");
$db->query("UPDATE con_material_categories SET item_type = 'Structural', pr_to_qtn_days = 5, cs_analysis_days = 2, approval_days = 10, wo_po_days = 1, adv_payment_days = 5, transport_days = 7, total_lead_days = 30 WHERE name IN ('Bricks', 'Brick')");
$db->query("UPDATE con_material_categories SET item_type = 'Structural', pr_to_qtn_days = 5, cs_analysis_days = 2, approval_days = 10, wo_po_days = 1, adv_payment_days = 5, transport_days = 7, total_lead_days = 30 WHERE name IN ('RMC', 'Ready Mix')");
$db->query("UPDATE con_material_categories SET item_type = 'Construction', pr_to_qtn_days = 4, cs_analysis_days = 2, approval_days = 3, wo_po_days = 1, adv_payment_days = 3, transport_days = 5, total_lead_days = 18 WHERE name = 'MS Items'");
$db->query("UPDATE con_material_categories SET item_type = 'Construction', pr_to_qtn_days = 2, cs_analysis_days = 2, approval_days = 3, wo_po_days = 1, adv_payment_days = 0, transport_days = 2, total_lead_days = 10 WHERE name = 'GI Items'");
$db->query("UPDATE con_material_categories SET item_type = 'Construction', pr_to_qtn_days = 6, cs_analysis_days = 2, approval_days = 3, wo_po_days = 1, adv_payment_days = 0, transport_days = 3, total_lead_days = 15 WHERE name = 'Safety Item'");
$db->query("UPDATE con_material_categories SET item_type = 'EME', pr_to_qtn_days = 5, cs_analysis_days = 1, approval_days = 6, wo_po_days = 1, adv_payment_days = 0, transport_days = 2, total_lead_days = 15 WHERE name IN ('Electrical Items', 'Electric Item')");
$db->query("UPDATE con_material_categories SET item_type = 'EME', pr_to_qtn_days = 2, cs_analysis_days = 2, approval_days = 3, wo_po_days = 1, adv_payment_days = 3, transport_days = 4, total_lead_days = 15 WHERE name = 'Mechanical Items'");
$db->query("UPDATE con_material_categories SET item_type = 'EME', pr_to_qtn_days = 5, cs_analysis_days = 2, approval_days = 3, wo_po_days = 1, adv_payment_days = 0, transport_days = 3, total_lead_days = 14 WHERE name = 'Fire Items'");
$db->query("UPDATE con_material_categories SET item_type = 'EME', pr_to_qtn_days = 8, cs_analysis_days = 2, approval_days = 3, wo_po_days = 1, adv_payment_days = 8, transport_days = 8, total_lead_days = 30 WHERE name = 'Domestic & Power Cable'");
$db->query("UPDATE con_material_categories SET item_type = 'Finishing', pr_to_qtn_days = 10, cs_analysis_days = 2, approval_days = 3, wo_po_days = 1, adv_payment_days = 4, transport_days = 60, total_lead_days = 80 WHERE name = 'Door/Frame'");
$db->query("UPDATE con_material_categories SET item_type = 'Finishing', pr_to_qtn_days = 6, cs_analysis_days = 2, approval_days = 20, wo_po_days = 1, adv_payment_days = 3, transport_days = 8, total_lead_days = 40 WHERE name IN ('Tiles Local', 'Tiles')");
$db->query("UPDATE con_material_categories SET item_type = 'Finishing', pr_to_qtn_days = 4, cs_analysis_days = 2, approval_days = 3, wo_po_days = 1, adv_payment_days = 0, transport_days = 3, total_lead_days = 13 WHERE name = 'Hardware Items'");
$db->query("UPDATE con_material_categories SET item_type = 'Finishing', pr_to_qtn_days = 10, cs_analysis_days = 3, approval_days = 10, wo_po_days = 2, adv_payment_days = 5, transport_days = 90, total_lead_days = 120 WHERE name = 'Lift /Gen/Sub.'");
echo "Update complete!";
