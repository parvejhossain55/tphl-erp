<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

mysqli_report(MYSQLI_REPORT_OFF);
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$id = 13;

$monthNames = ['','January','February','March','April','May','June',
                  'July','August','September','October','November','December'];

// Get all target entries for this project
$stmt = $db->prepare("
    SELECT tf.*, wp.work_description AS plan_description,
           wt.work_type_name AS work_type_name,
           dt.name AS design_type_name, u.full_name AS created_by_name
    FROM   target_fixed tf
    LEFT JOIN project_working_plans wp ON wp.id = tf.work_plan_id
    LEFT JOIN work_types            wt ON wt.id = wp.work_type_id
    LEFT JOIN design_types          dt ON dt.id = wt.design_type_id
    LEFT JOIN users                 u  ON u.id  = tf.user_id
    WHERE  tf.project_id = ?
    ORDER  BY tf.year ASC, tf.month ASC, tf.week_number ASC");
$stmt->bind_param('i', $id);
$stmt->execute();
$allTargets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Build active months list
$activeMonths = [];
foreach ($allTargets as $t) {
    $mk = $t['year'] . '-' . str_pad($t['month'], 2, '0', STR_PAD_LEFT);
    $activeMonths[$mk] = true;
}

// Get months from DPRs
$stmtDpr = $db->prepare("SELECT DISTINCT YEAR(report_date) as yr, MONTH(report_date) as mo FROM weekly_progress_report WHERE project_id = ?");
$stmtDpr->bind_param('i', $id);
$stmtDpr->execute();
$dprRows = $stmtDpr->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($dprRows as $row) {
    if (!$row['yr'] || !$row['mo']) continue;
    $mk = $row['yr'] . '-' . str_pad($row['mo'], 2, '0', STR_PAD_LEFT);
    $activeMonths[$mk] = true;
}

// Get months from financials
$stmtFin = $db->prepare("SELECT DISTINCT YEAR(transaction_date) as yr, MONTH(transaction_date) as mo FROM con_financial_transactions WHERE project_id = ?");
$stmtFin->bind_param('i', $id);
$stmtFin->execute();
$finRows = $stmtFin->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($finRows as $row) {
    if (!$row['yr'] || !$row['mo']) continue;
    $mk = $row['yr'] . '-' . str_pad($row['mo'], 2, '0', STR_PAD_LEFT);
    $activeMonths[$mk] = true;
}

krsort($activeMonths); // sort descending

$result = [];
foreach (array_keys($activeMonths) as $mk) {
    [$yr, $mo] = explode('-', $mk);
    $yr = (int)$yr;
    $mo = (int)$mo;
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $mo, $yr);

    $monthEntry = [
        'month_key'   => $mk,
        'month_label' => $monthNames[$mo] . ' ' . $yr,
        'year'        => $yr,
        'month'       => $mo,
        'weeks'       => [],
    ];

    // A month can have up to 5 weeks
    for ($wNum = 1; $wNum <= 5; $wNum++) {
        $dayStart = ($wNum - 1) * 7 + 1;
        if ($dayStart > $daysInMonth) break; // if week 5 start day exceeds month days
        
        $dayEnd = $wNum * 7;
        if ($wNum === 5 || $dayEnd > $daysInMonth) {
            $dayEnd = $daysInMonth;
        }
        
        $dateFrom = sprintf('%04d-%02d-%02d', $yr, $mo, $dayStart);
        $dateTo   = sprintf('%04d-%02d-%02d', $yr, $mo, $dayEnd);

        // Filter targets for this week
        $targets = array_filter($allTargets, function($t) use ($yr, $mo, $wNum) {
            return $t['year'] == $yr && $t['month'] == $mo && $t['week_number'] == $wNum;
        });

        // DPR entries for this week
        $s2 = $db->prepare("
            SELECT dpr.*, se.full_name AS engineer_name, t.target_title,
                   pm.full_name AS pm_name, qc.full_name AS qc_name, cb.full_name AS creator_name,
                   wp.work_description AS work_plan_title,
                   wo.title AS work_order_title, c.name AS contractor_name, c.phone AS contractor_phone,
                   t.year AS target_year, t.month AS target_month, t.week_number
            FROM   weekly_progress_report dpr
            LEFT JOIN users se ON se.id = dpr.site_engineer_id
            LEFT JOIN users pm ON pm.id = dpr.approved_by_pm
            LEFT JOIN users qc ON qc.id = dpr.approved_by_qc
            LEFT JOIN users cb ON cb.id = dpr.created_by
            LEFT JOIN target_fixed t ON t.id = dpr.target_id
            LEFT JOIN project_working_plans wp ON wp.id = dpr.work_plan_id
            LEFT JOIN con_work_orders wo ON wo.id = dpr.work_order_id
            LEFT JOIN contractors c ON c.id = wo.contractor_id
            WHERE  dpr.project_id = ? AND dpr.report_date BETWEEN ? AND ?
            ORDER  BY dpr.report_date ASC");
        $s2->bind_param('iss', $id, $dateFrom, $dateTo);
        $s2->execute();
        $dprs = $s2->get_result()->fetch_all(MYSQLI_ASSOC);

        // Financial transactions for this week
        $s3 = $db->prepare("
            SELECT cft.*, u.full_name AS processor_name
            FROM   con_financial_transactions cft
            LEFT JOIN users u ON u.id = cft.processed_by
            WHERE  cft.project_id = ? AND DATE(cft.transaction_date) BETWEEN ? AND ?
            ORDER  BY cft.transaction_date ASC");
        $s3->bind_param('iss', $id, $dateFrom, $dateTo);
        $s3->execute();
        $transactions = $s3->get_result()->fetch_all(MYSQLI_ASSOC);

        // If week has no data whatsoever, optionally skip it (but usually good to show empty week if month is active)
        if (empty($targets) && empty($dprs) && empty($transactions)) {
            continue; 
        }

        $monthEntry['weeks'][] = [
            'week_num'     => $wNum,
            'week_label'   => 'Week ' . $wNum,
            'date_from'    => $dateFrom,
            'date_to'      => $dateTo,
            'targets'      => array_values($targets),
            'dprs'         => $dprs,
            'transactions' => $transactions,
        ];
    }

    if (!empty($monthEntry['weeks'])) {
        $result[] = $monthEntry;
    }
}

echo json_encode(['success' => true, 'data' => $result]);
