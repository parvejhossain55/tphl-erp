import re

filename = r'c:\xampp\htdocs\TPHL-ERP\app\models\Lead.php'
with open(filename, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Date range filter
date_range_regex = re.compile(
    r'(// Date range filter\s+if \(!empty\(\$from_date\) && !empty\(\$to_date\)\) \{\s+\$sql \.= " AND DATE\(l\.created_at\) BETWEEN \? AND \?";.*?// Month filter.*?\}\s+\})',
    re.DOTALL
)

def replace_date_range(m):
    return '''// Determine if we should apply created_at date filter
        $applyCreatedDateFilter = true;
        if (in_array($filter_card, ['total_booked', 'today_follow_up', 'pending_follow_ups'])) {
            $applyCreatedDateFilter = false;
        }

        // Date range filter
        if ($applyCreatedDateFilter) {
            if (!empty($from_date) && !empty($to_date)) {
                $sql .= " AND DATE(l.created_at) BETWEEN ? AND ?";
                $params[] = $from_date;
                $params[] = $to_date;
                $types .= "ss";
            } elseif (!empty($from_date)) {
                $sql .= " AND DATE(l.created_at) >= ?";
                $params[] = $from_date;
                $types .= "s";
            } elseif (!empty($to_date)) {
                $sql .= " AND DATE(l.created_at) <= ?";
                $params[] = $to_date;
                $types .= "s";
            } elseif (!empty($date)) {
                $sql .= " AND DATE(l.created_at) = ?";
                $params[] = $date;
                $types .= "s";
            } else {
                // Year filter
                if (!empty($year)) {
                    $sql .= " AND YEAR(l.created_at) = ?";
                    $params[] = $year;
                    $types .= "i";
                }
                // Month filter
                if (!empty($month)) {
                    $sql .= " AND MONTH(l.created_at) = ?";
                    $params[] = $month;
                    $types .= "i";
                }
            }
        }'''

content = date_range_regex.sub(replace_date_range, content)

# 2. Filter card logic
filter_card_regex = re.compile(
    r'(if \(\$filter_card === \'total_booked\'\) \{.*?\} elseif \(\$filter_card === \'pending_follow_ups\'\) \{.*?\})',
    re.DOTALL
)

def replace_filter_card(m):
    return '''if ($filter_card === 'total_booked') {
                $sql .= " AND l.last_lead_action = 'booked'";
                if (!empty($from_date) && !empty($to_date)) {
                    $sql .= " AND EXISTS (SELECT 1 FROM lead_bookings lb_sub WHERE lb_sub.lead_id = l.id AND lb_sub.deleted_at IS NULL AND DATE(lb_sub.booking_date) BETWEEN ? AND ?)";
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $types .= "ss";
                }
            } elseif ($filter_card === 'today_follow_up') {
                if (!empty($from_date) && !empty($to_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND DATE(lf_sub.next_follow_up_date) BETWEEN ? AND ?
                    )";
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $types .= "ss";
                } else {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND lf_sub.next_follow_up_date >= CURDATE()
                          AND lf_sub.next_follow_up_date < CURDATE() + INTERVAL 1 DAY
                    )";
                }
            } elseif ($filter_card === 'pending_follow_ups') {
                if (!empty($from_date) && !empty($to_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND lf_sub.status = 'pending'
                          AND DATE(lf_sub.next_follow_up_date) BETWEEN ? AND ?
                    )";
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $types .= "ss";
                } else {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND lf_sub.next_follow_up_date >= CURDATE()
                          AND lf_sub.next_follow_up_date < CURDATE() + INTERVAL 1 DAY
                          AND lf_sub.status = 'pending'
                    )";
                }
            }'''

content = filter_card_regex.sub(replace_filter_card, content)

with open(filename, 'w', encoding='utf-8') as f:
    f.write(content)
print('Done!')
