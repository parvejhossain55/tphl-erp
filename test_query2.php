<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) die("Connection failed: " . $db->connect_error);

$query = "
            SELECT pr.id AS pr_id
            FROM purchase_requests pr
            JOIN purchase_request_items pri ON pri.request_id = pr.id
            WHERE pr.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
            GROUP BY pr.id
        ";
        
$stmt = $db->prepare($query);
$stmt->execute();
$result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$prIds = array_unique(array_column($result, 'pr_id'));
if (!empty($prIds)) {
    $inClause = implode(',', $prIds);
    $recvQuery = "
        SELECT 
            pri.request_id,
            SUM(pri.quantity) AS total_req,
            COALESCE((
                SELECT SUM(mri.received_quantity)
                FROM material_receiving_items mri
                JOIN material_receiving mr ON mr.id = mri.receiving_id
                JOIN purchase_orders po ON po.id = mr.purchase_order_id
                WHERE po.request_id = pri.request_id AND mri.status = 'Accepted'
            ), 0) AS total_recv
        FROM purchase_request_items pri
        WHERE pri.request_id IN ($inClause)
        GROUP BY pri.request_id
    ";
    
    $start = microtime(true);
    $recvRes = $db->query($recvQuery);
    if (!$recvRes) {
        echo "Error: " . $db->error . "\n";
    } else {
        echo "Time taken: " . (microtime(true) - $start) . "\n";
        echo "Success, rows: " . $recvRes->num_rows . "\n";
    }
}
