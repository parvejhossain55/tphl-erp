<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$sql = "SELECT tf.id, tf.target_title,
               (SELECT id FROM boq_master WHERE target_id = tf.id LIMIT 1) AS boq_id,
               (SELECT status FROM boq_master WHERE target_id = tf.id LIMIT 1) AS boq_status,
               (SELECT u.full_name FROM boq_master bm JOIN users u ON u.id = bm.approved_by WHERE bm.target_id = tf.id LIMIT 1) AS boq_approved_by_name,
               (SELECT approved_at FROM boq_master WHERE target_id = tf.id LIMIT 1) AS boq_approved_at,
               (SELECT id FROM purchase_requests WHERE bom_id = (SELECT id FROM boq_master WHERE target_id = tf.id LIMIT 1) LIMIT 1) AS pr_id,
               (SELECT status FROM purchase_requests WHERE bom_id = (SELECT id FROM boq_master WHERE target_id = tf.id LIMIT 1) LIMIT 1) AS pr_status,
               (SELECT approved_at FROM purchase_requests WHERE bom_id = (SELECT id FROM boq_master WHERE target_id = tf.id LIMIT 1) LIMIT 1) AS pr_approved_at,
               (SELECT u.full_name FROM purchase_requests pr JOIN users u ON u.id = pr.approved_by_id WHERE pr.bom_id = (SELECT id FROM boq_master WHERE target_id = tf.id LIMIT 1) LIMIT 1) AS pr_approved_by_name,
               (SELECT COUNT(*) FROM quotation_comparisons WHERE request_id = (SELECT id FROM purchase_requests WHERE bom_id = (SELECT id FROM boq_master WHERE target_id = tf.id LIMIT 1) LIMIT 1)) AS has_cst,
               (SELECT COUNT(*) FROM comparison_items ci JOIN quotation_comparisons qc ON qc.id = ci.comparison_id WHERE qc.request_id = (SELECT id FROM purchase_requests WHERE bom_id = (SELECT id FROM boq_master WHERE target_id = tf.id LIMIT 1) LIMIT 1) AND ci.is_selected = 1) AS vendor_selected
        FROM target_fixed tf
        ORDER BY tf.id DESC LIMIT 5";

$result = $db->query($sql);
if ($result) {
    echo "Query runs successfully!\n";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Query error: " . $db->error . "\n";
}
?>
