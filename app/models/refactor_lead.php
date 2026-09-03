<?php

$filename = __DIR__ . '/Lead.php';
$content = file_get_contents($filename);

$replace_date_range = <<<EOD
// Determine if we should apply created_at date filter
        \$applyCreatedDateFilter = true;
        if (in_array(\$filter_card, ['total_booked', 'today_follow_up', 'pending_follow_ups'])) {
            \$applyCreatedDateFilter = false;
        }

        // Date range filter
        if (\$applyCreatedDateFilter) {
            if (!empty(\$from_date) && !empty(\$to_date)) {
                \$sql .= " AND DATE(l.created_at) BETWEEN ? AND ?";
                \$params[] = \$from_date;
                \$params[] = \$to_date;
                \$types .= "ss";
            } elseif (!empty(\$from_date)) {
                \$sql .= " AND DATE(l.created_at) >= ?";
                \$params[] = \$from_date;
                \$types .= "s";
            } elseif (!empty(\$to_date)) {
                \$sql .= " AND DATE(l.created_at) <= ?";
                \$params[] = \$to_date;
                \$types .= "s";
            } elseif (!empty(\$date)) {
                \$sql .= " AND DATE(l.created_at) = ?";
                \$params[] = \$date;
                \$types .= "s";
            } else {
                // Year filter
                if (!empty(\$year)) {
                    \$sql .= " AND YEAR(l.created_at) = ?";
                    \$params[] = \$year;
                    \$types .= "i";
                }
                // Month filter
                if (!empty(\$month)) {
                    \$sql .= " AND MONTH(l.created_at) = ?";
                    \$params[] = \$month;
                    \$types .= "i";
                }
            }
        }
EOD;

$content = preg_replace(
    '/(?:\/\/ Date range filter\s+if \(!empty\(\$from_date\) && !empty\(\$to_date\)\) \{\s+\$sql \.= " AND DATE\(l\.created_at\) BETWEEN \? AND \?";.*? \/\/ Month filter.*?\}\s+\})/s',
    $replace_date_range,
    $content
);


$replace_filter_card = <<<EOD
if (\$filter_card === 'total_booked') {
                \$sql .= " AND l.last_lead_action = 'booked'";
                if (!empty(\$from_date) && !empty(\$to_date)) {
                    \$sql .= " AND EXISTS (SELECT 1 FROM lead_bookings lb_sub WHERE lb_sub.lead_id = l.id AND lb_sub.deleted_at IS NULL AND DATE(lb_sub.booking_date) BETWEEN ? AND ?)";
                    \$params[] = \$from_date;
                    \$params[] = \$to_date;
                    \$types .= "ss";
                }
            } elseif (\$filter_card === 'today_follow_up') {
                if (!empty(\$from_date) && !empty(\$to_date)) {
                    \$sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND DATE(lf_sub.next_follow_up_date) BETWEEN ? AND ?
                    )";
                    \$params[] = \$from_date;
                    \$params[] = \$to_date;
                    \$types .= "ss";
                } else {
                    \$sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND lf_sub.next_follow_up_date >= CURDATE()
                          AND lf_sub.next_follow_up_date < CURDATE() + INTERVAL 1 DAY
                    )";
                }
            } elseif (\$filter_card === 'pending_follow_ups') {
                if (!empty(\$from_date) && !empty(\$to_date)) {
                    \$sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND lf_sub.status = 'pending'
                          AND DATE(lf_sub.next_follow_up_date) BETWEEN ? AND ?
                    )";
                    \$params[] = \$from_date;
                    \$params[] = \$to_date;
                    \$types .= "ss";
                } else {
                    \$sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND lf_sub.next_follow_up_date >= CURDATE()
                          AND lf_sub.next_follow_up_date < CURDATE() + INTERVAL 1 DAY
                          AND lf_sub.status = 'pending'
                    )";
                }
            }
EOD;

$content = preg_replace(
    '/(?:if \(\$filter_card === \'total_booked\'\) \{.*?\} elseif \(\$filter_card === \'pending_follow_ups\'\) \{.*?\})/s',
    $replace_filter_card,
    $content
);


file_put_contents($filename, $content);
echo "Done!\n";
