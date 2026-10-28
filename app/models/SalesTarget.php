<?php
class SalesTarget extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }
    public function getAllTargets($userId)
    {
        $sql = "SELECT st.*, c.name as category_name 
            FROM sales_targets st
            LEFT JOIN project_categories c ON st.category_id = c.id
            WHERE st.sales_user_id = ?
            ORDER BY st.month DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $targets = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        // Preload category map for multiple categories display
        $catRes = $this->db->query("SELECT id, name FROM project_categories");
        $catMap = [];
        if ($catRes) {
            while ($c = $catRes->fetch_assoc()) {
                $catMap[$c['id']] = $c['name'];
            }
        }

        foreach ($targets as &$t) {
            $catNames = [];
            if (!empty($t['category_ids'])) {
                $cIds = json_decode($t['category_ids'], true);
                if (is_array($cIds)) {
                    foreach ($cIds as $cid) {
                        if (isset($catMap[$cid])) {
                            $catNames[] = $catMap[$cid];
                        }
                    }
                }
            }
            if (empty($catNames) && !empty($t['category_name'])) {
                $catNames[] = $t['category_name'];
            }
            $t['category_names_display'] = !empty($catNames) ? implode(', ', $catNames) : 'All / None';
            
            // Map Projects
            $projNames = [];
            if (!empty($t['project_ids'])) {
                $pIds = json_decode($t['project_ids'], true);
                if (is_array($pIds) && !empty($pIds)) {
                    $pIdsStr = implode(',', array_map('intval', $pIds));
                    $pRes = $this->db->query("SELECT name FROM projects WHERE id IN ($pIdsStr)");
                    if ($pRes) {
                        while ($p = $pRes->fetch_assoc()) {
                            $projNames[] = $p['name'];
                        }
                    }
                }
            }
            $t['project_names_display'] = $projNames;

            // Map Flats
            $flatNames = [];
            if (!empty($t['flat_ids'])) {
                $fIds = json_decode($t['flat_ids'], true);
                if (is_array($fIds) && !empty($fIds)) {
                    $fIdsStr = implode(',', array_map('intval', $fIds));
                    $fRes = $this->db->query("SELECT f.flat_name, p.name as project_name FROM flats f JOIN projects p ON f.project_id = p.id WHERE f.id IN ($fIdsStr)");
                    if ($fRes) {
                        while ($f = $fRes->fetch_assoc()) {
                            $flatNames[] = $f['project_name'] . ' - ' . $f['flat_name'];
                        }
                    }
                }
            }
            $t['flat_names_display'] = $flatNames;
        }
        unset($t);

        return $targets;
    }

    /**
     * Get the latest target details for an array of sales user IDs
     * Batch queries categories, projects, and flats for optimal performance.
     *
     * @param array $userIds
     * @return array Keyed by sales_user_id
     */
    public function getLatestTargetsForUsers(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $userIdsClean = array_values(array_filter(array_map('intval', $userIds)));
        if (empty($userIdsClean)) {
            return [];
        }

        $inClause = implode(',', $userIdsClean);
        $sql = "SELECT st.*, c.name as category_name 
                FROM sales_targets st
                LEFT JOIN project_categories c ON st.category_id = c.id
                WHERE st.sales_user_id IN ($inClause)
                ORDER BY st.month DESC, st.id DESC";

        $res = $this->db->query($sql);
        if (!$res) {
            return [];
        }

        $allRows = $res->fetch_all(MYSQLI_ASSOC);
        $latestByUser = [];
        foreach ($allRows as $row) {
            $uid = (int)$row['sales_user_id'];
            if (!isset($latestByUser[$uid])) {
                $latestByUser[$uid] = $row;
            }
        }

        if (empty($latestByUser)) {
            return [];
        }

        // Collect all IDs to batch fetch
        $allCatIds = [];
        $allProjIds = [];
        $allFlatIds = [];

        foreach ($latestByUser as $t) {
            if (!empty($t['category_ids'])) {
                $cIds = json_decode($t['category_ids'], true);
                if (is_array($cIds)) {
                    foreach ($cIds as $cid) {
                        $allCatIds[] = (int)$cid;
                    }
                }
            }
            if (!empty($t['category_id'])) {
                $allCatIds[] = (int)$t['category_id'];
            }
            if (!empty($t['project_ids'])) {
                $pIds = json_decode($t['project_ids'], true);
                if (is_array($pIds)) {
                    foreach ($pIds as $pid) {
                        $allProjIds[] = (int)$pid;
                    }
                }
            }
            if (!empty($t['flat_ids'])) {
                $fIds = json_decode($t['flat_ids'], true);
                if (is_array($fIds)) {
                    foreach ($fIds as $fid) {
                        $allFlatIds[] = (int)$fid;
                    }
                }
            }
        }

        // Fetch categories map
        $catMap = [];
        if (!empty($allCatIds)) {
            $cStr = implode(',', array_unique($allCatIds));
            $cRes = $this->db->query("SELECT id, name FROM project_categories WHERE id IN ($cStr)");
            if ($cRes) {
                while ($c = $cRes->fetch_assoc()) {
                    $catMap[$c['id']] = $c['name'];
                }
            }
        }

        // Fetch projects map
        $projMap = [];
        if (!empty($allProjIds)) {
            $pStr = implode(',', array_unique($allProjIds));
            $pRes = $this->db->query("SELECT id, name FROM projects WHERE id IN ($pStr)");
            if ($pRes) {
                while ($p = $pRes->fetch_assoc()) {
                    $projMap[$p['id']] = $p['name'];
                }
            }
        }

        // Fetch flats map
        $flatMap = [];
        if (!empty($allFlatIds)) {
            $fStr = implode(',', array_unique($allFlatIds));
            $fRes = $this->db->query("SELECT f.id, f.flat_name, p.name as project_name FROM flats f JOIN projects p ON f.project_id = p.id WHERE f.id IN ($fStr)");
            if ($fRes) {
                while ($f = $fRes->fetch_assoc()) {
                    $flatMap[$f['id']] = [
                        'flat_name' => $f['flat_name'],
                        'project_name' => $f['project_name'],
                        'display' => $f['project_name'] . ' - ' . $f['flat_name']
                    ];
                }
            }
        }

        // Enrich each target
        foreach ($latestByUser as $uid => &$t) {
            // Category names
            $catNames = [];
            if (!empty($t['category_ids'])) {
                $cIds = json_decode($t['category_ids'], true);
                if (is_array($cIds)) {
                    foreach ($cIds as $cid) {
                        if (isset($catMap[$cid])) {
                            $catNames[] = $catMap[$cid];
                        }
                    }
                }
            }
            if (empty($catNames) && !empty($t['category_id']) && isset($catMap[$t['category_id']])) {
                $catNames[] = $catMap[$t['category_id']];
            }
            $t['category_names_display'] = !empty($catNames) ? implode(', ', $catNames) : '';

            // Project names
            $projNames = [];
            $pIds = !empty($t['project_ids']) ? array_unique((array)json_decode($t['project_ids'], true)) : [];
            if (is_array($pIds)) {
                foreach ($pIds as $pid) {
                    if (isset($projMap[$pid])) {
                        $projNames[] = $projMap[$pid];
                    }
                }
            }
            $t['project_names_display'] = array_values(array_unique($projNames));

            // Flat names
            $flatNames = [];
            $fIds = !empty($t['flat_ids']) ? array_unique((array)json_decode($t['flat_ids'], true)) : [];
            if (is_array($fIds)) {
                foreach ($fIds as $fid) {
                    if (isset($flatMap[$fid])) {
                        $flatNames[] = $flatMap[$fid]['display'];
                    }
                }
            }
            $t['flat_names_display'] = $flatNames;

            // Projects and Units summary logic
            if (!empty($projNames)) {
                $t['projects_summary'] = implode(', ', $projNames);
                if (!empty($flatNames)) {
                    $t['units_summary'] = count($flatNames) . ' Unit' . (count($flatNames) > 1 ? 's' : '');
                    $t['units_is_all'] = false;
                } else {
                    $t['units_summary'] = 'All Units';
                    $t['units_is_all'] = true;
                }
            } else {
                if (!empty($t['category_names_display'])) {
                    $t['projects_summary'] = $t['category_names_display'] . ' (All Projects)';
                } else {
                    $t['projects_summary'] = 'All Projects';
                }
                $t['units_summary'] = 'All Units';
                $t['units_is_all'] = true;
            }

            // Month display
            $monthTime = strtotime($t['month'] ?? 'now');
            $t['month_display'] = date('M Y', $monthTime);

            // Period display
            if (!empty($t['from_date']) && !empty($t['to_date'])) {
                $t['period_display'] = date('d M', strtotime($t['from_date'])) . ' - ' . date('d M Y', strtotime($t['to_date']));
            } else {
                $t['period_display'] = date('M Y', $monthTime);
            }
        }
        unset($t);

        return $latestByUser;
    }


    /**
     * Insert or update target with multiple categories, multiple projects and custom date range
     */
    public function saveTarget($userId, $month, $siteVisit, $bookingTarget, $bookingValue, $categoryId = null, $projectIds = null, $salesRole = null, $categoryIds = null, $fromDate = null, $toDate = null, $flatIds = null, $siteVisitLeads = 0)
    {
        if (is_array($categoryIds)) {
            if (empty($categoryId) && !empty($categoryIds)) {
                $categoryId = (int)reset($categoryIds);
            }
            $categoryIds = json_encode(array_values(array_map('strval', $categoryIds)));
        }

        $sql = "
            INSERT INTO sales_targets
            (sales_user_id, month, from_date, to_date, site_visit_target, booking_target, booking_value_target, category_id, category_ids, project_ids, sales_role, flat_ids, site_visit_leads_target)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                from_date = VALUES(from_date),
                to_date = VALUES(to_date),
                site_visit_target = VALUES(site_visit_target),
                booking_target = VALUES(booking_target),
                booking_value_target = VALUES(booking_value_target),
                category_id = VALUES(category_id),
                category_ids = VALUES(category_ids),
                project_ids = VALUES(project_ids),
                sales_role = VALUES(sales_role),
                flat_ids = VALUES(flat_ids),
                site_visit_leads_target = VALUES(site_visit_leads_target)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "isssiidissssi",
            $userId,
            $month,
            $fromDate,
            $toDate,
            $siteVisit,
            $bookingTarget,
            $bookingValue,
            $categoryId,
            $categoryIds,
            $projectIds,
            $salesRole,
            $flatIds,
            $siteVisitLeads
        );
        return $stmt->execute();
    }

    public function getTargetProgress($userId, $month, $year)
    {
        $target = $this->getTarget($userId, sprintf('%04d-%02d', $year, $month));
        if (!empty($target['from_date']) && !empty($target['to_date'])) {
            $start = $target['from_date'] . ' 00:00:00';
            $end = $target['to_date'] . ' 23:59:59';
        } else {
            $cycle = $this->getCycleDateRange($year, $month);
            $start = $cycle['start'];
            $end = $cycle['end'];
        }

        // Booking
        $sql1 = "SELECT COUNT(*) AS deals_closed, 
                    SUM(agreement_value - IFNULL(discount_amount,0)) AS total_value
             FROM lead_bookings
             WHERE sales_by_id = ? AND booking_status = 'completed' 
                   AND booking_date BETWEEN ? AND ?";
        $stmt1 = $this->db->prepare($sql1);
        $stmt1->bind_param("iss", $userId, $start, $end);
        $stmt1->execute();
        $booking = $stmt1->get_result()->fetch_assoc();

        // Visits
        $sql2 = "SELECT COUNT(*) AS visits_done
             FROM lead_follow_ups
             WHERE assigned_with = ? AND follow_up_type='visit' 
                   AND status='completed' AND follow_up_date BETWEEN ? AND ?";
        $stmt2 = $this->db->prepare($sql2);
        $stmt2->bind_param("iss", $userId, $start, $end);
        $stmt2->execute();
        $visits = $stmt2->get_result()->fetch_assoc();

        return [
            'deals_closed' => $booking['deals_closed'] ?? 0,
            'total_booking_value' => $booking['total_value'] ?? 0,
            'visits_done' => $visits['visits_done'] ?? 0
        ];
    }

    // Get target for user + month
    public function getTarget($userId, $month)
    {
        $sql = "SELECT * FROM sales_targets WHERE sales_user_id = ? AND DATE_FORMAT(month,'%Y-%m') = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $userId, $month);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    // Get completed site visits for a month
    public function getMonthlyVisitCompleted($userId, $month)
    {
        $target = $this->getTarget($userId, $month);
        if (!empty($target['from_date']) && !empty($target['to_date'])) {
            $start = $target['from_date'] . ' 00:00:00';
            $end = $target['to_date'] . ' 23:59:59';
        } else {
            list($y, $m) = explode('-', $month);
            $cycle = $this->getCycleDateRange($y, $m);
            $start = $cycle['start'];
            $end = $cycle['end'];
        }

        $sql = "SELECT COUNT(*) as completed FROM lead_follow_ups
                WHERE assigned_with=? AND follow_up_type='visit' AND status='completed'
                  AND follow_up_date BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iss", $userId, $start, $end);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['completed'] ?? 0;
    }

    // Get completed bookings for a month
    public function getMonthlyBookingCompleted($userId, $month)
    {
        $target = $this->getTarget($userId, $month);
        if (!empty($target['from_date']) && !empty($target['to_date'])) {
            $start = $target['from_date'] . ' 00:00:00';
            $end = $target['to_date'] . ' 23:59:59';
        } else {
            list($y, $m) = explode('-', $month);
            $cycle = $this->getCycleDateRange($y, $m);
            $start = $cycle['start'];
            $end = $cycle['end'];
        }

        $sql = "
        SELECT COUNT(*) AS total
        FROM lead_bookings
        WHERE sales_by_id = ?
          AND booking_date BETWEEN ? AND ?
          AND booking_status IN ('completed','booked')
    ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iss", $userId, $start, $end);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int)($result['total'] ?? 0);
    }

    // Total Deals Closed (all time, not month restricted)
    public function getDealsClosedAllTime($userId)
    {
        $sql = "
        SELECT COUNT(DISTINCT lf.lead_id) AS total
        FROM lead_follow_ups lf
        JOIN leads l ON l.id = lf.lead_id
        WHERE lf.assigned_with = ?
          AND lf.follow_up_type = 'visit'
          AND lf.status != 'canceled'
    ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int)($result['total'] ?? 0);
    }

    // Total Deals Won (all time, not month restricted)
    public function getDealsWonAllTime($userId)
    {
        $sql = "
        SELECT COUNT(*) AS total
        FROM leads
        WHERE last_lead_action = 'booked'
          AND assigned_id = ?
    ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int)($result['total'] ?? 0);
    }

    public function getAllDealsLost($userId)
    {
        $sql = "
        SELECT COUNT(DISTINCT l.id) AS total
        FROM leads l
        LEFT JOIN lead_follow_ups lf ON lf.lead_id = l.id
        WHERE l.assigned_id = ?
          AND (
               l.last_lead_action = 'canceled'
               OR (lf.follow_up_type = 'visit' AND lf.status = 'canceled')
          )
    ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int)($result['total'] ?? 0);
    }


    public function getDealsClosed($userId, $month)
    {
        list($y, $m) = explode('-', $month);
        $cycle = $this->getCycleDateRange($y, $m);
        $start = $cycle['start'];
        $end = $cycle['end'];

        $sql = "
        SELECT COUNT(DISTINCT lf.lead_id) AS total
        FROM lead_follow_ups lf
        JOIN leads l ON l.id = lf.lead_id
        WHERE lf.assigned_with = ?
          AND lf.follow_up_date BETWEEN ? AND ?
          AND lf.follow_up_type = 'visit'
          AND lf.status != 'canceled'
    ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iss", $userId, $start, $end);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int)($result['total'] ?? 0);
    }

    public function getDealsWon($userId, $month)
    {
        list($y, $m) = explode('-', $month);
        $cycle = $this->getCycleDateRange($y, $m);
        $start = $cycle['start'];
        $end = $cycle['end'];

        $sql = "
        SELECT COUNT(*) AS total
        FROM leads
        WHERE last_lead_action = 'booked'
          AND assigned_id  = ?
          AND updated_at BETWEEN ? AND ?
    ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iss", $userId, $start, $end);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int)($result['total'] ?? 0);
    }

    public function getDealsLost($userId, $month)
    {
        list($y, $m) = explode('-', $month);
        $cycle = $this->getCycleDateRange($y, $m);
        $start = $cycle['start'];
        $end = $cycle['end'];

        $sql = "
        SELECT COUNT(*) AS total
        FROM leads l
        LEFT JOIN lead_follow_ups lf ON lf.lead_id = l.id
        WHERE l.assigned_id = ?
          AND l.updated_at BETWEEN ? AND ?
          AND (
               l.last_lead_action = 'canceled'
               OR (lf.follow_up_type = 'visit' AND lf.status = 'canceled')
          )
    ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iss", $userId, $start, $end);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int)($result['total'] ?? 0);
    }


    // public function getAllExecutiveMonthlyStats($month)
    // {
    //     $sql = "
    //         SELECT 
    //             u.id,
    //             u.name,

    //             IFNULL(st.booking_target, 0) AS booking_target,

    //             IFNULL(b.booking_completed, 0) AS booking_completed

    //         FROM users u

    //         LEFT JOIN sales_targets st 
    //             ON st.sales_user_id = u.id
    //             AND DATE_FORMAT(st.month, '%Y-%m') = ?

    //         LEFT JOIN (
    //             SELECT 
    //                 sales_by_id,
    //                 COUNT(*) AS booking_completed
    //             FROM lead_bookings
    //             WHERE booking_status = 'completed'
    //               AND DATE_FORMAT(booking_date, '%Y-%m') = ?
    //               AND sales_by_id IS NOT NULL
    //             GROUP BY sales_by_id
    //         ) b ON b.sales_by_id = u.id

    //         WHERE u.role = 'sales_executive'

    //         ORDER BY u.name ASC
    //     ";

    //     $stmt = $this->db->prepare($sql);
    //     $stmt->bind_param("ss", $month, $month);
    //     $stmt->execute();

    //     return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    // }


public function getExecutiveBookingStats($type = 'monthly')
{
    //
    if ($type === 'weekly') {
        $dateCondition   = "YEARWEEK(lb.booking_date, 1) = YEARWEEK(CURDATE(), 1)";
        $targetCondition = "YEARWEEK(month, 1) = YEARWEEK(CURDATE(), 1)";
    } elseif ($type === 'yearly') {
        $currentYear     = date('Y');
        $dateCondition   = "YEAR(lb.booking_date) = $currentYear";
        $targetCondition = "YEAR(month) = $currentYear";
    } elseif ($type === 'fullData') {
        // Full data → ignore date filter
        $dateCondition   = "1"; // always true
        $targetCondition = "1"; // always true
    } else {
        // Default → monthly (26th to 25th)
        $y = (int)date('Y');
        $m = (int)date('m');
        $cycle = $this->getCycleDateRange($y, $m);
        $start = $cycle['start'];
        $end = $cycle['end'];
        
        $dateCondition   = "lb.booking_date BETWEEN '$start' AND '$end'";
        $targetCondition = "DATE_FORMAT(month, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
    }

    //  Build SQL
    if ($type === 'yearly' || $type === 'fullData') {
        $sql = "SELECT 
                u.id,
                u.full_name AS name,
                IFNULL(st.total_target,0) AS booking_target,
                IFNULL(b.completed,0) AS booking_completed,
                IFNULL(b.total_flat_value, 0) AS total_flat_value,
                IFNULL(b.total_booking_money, 0) AS total_booking_money,
                IFNULL(b.total_registration_money, 0) AS total_registration_money
            FROM users u
            LEFT JOIN employees emp ON (emp.user_id = u.id OR emp.id = u.employee_id)
            LEFT JOIN (
                SELECT sales_user_id, SUM(booking_target) AS total_target
                FROM sales_targets
                GROUP BY sales_user_id
            ) st ON st.sales_user_id = u.id
            LEFT JOIN (
                SELECT 
                    lb.sales_by_id, 
                    COUNT(DISTINCT lb.id) AS completed,
                    SUM(COALESCE(NULLIF(lb.agreement_value, 0), f.price_value, 0)) AS total_flat_value,
                    SUM(IFNULL(pay_b.booking_amount, 0)) AS total_booking_money,
                    SUM(IFNULL(pay_r.registration_amount, 0)) AS total_registration_money
                FROM lead_bookings lb
                LEFT JOIN flats f ON f.id = lb.flat_id
                LEFT JOIN (
                    SELECT booking_id, SUM(amount) AS booking_amount
                    FROM lead_payments
                    WHERE payment_type = 'booking_money'
                      AND payment_status IN ('paid', 'partially_paid')
                      AND verification_status = 'verified'
                    GROUP BY booking_id
                ) pay_b ON pay_b.booking_id = lb.id
                LEFT JOIN (
                    SELECT booking_id, SUM(amount) AS registration_amount
                    FROM lead_payments
                    WHERE payment_type = 'registration'
                      AND payment_status IN ('paid', 'partially_paid')
                      AND verification_status = 'verified'
                    GROUP BY booking_id
                ) pay_r ON pay_r.booking_id = lb.id
                WHERE lb.booking_status IN ('completed', 'booked')
                  AND lb.deleted_at IS NULL
                  " . ($type === 'yearly' ? "AND YEAR(lb.booking_date) = YEAR(CURDATE())" : "") . "
                GROUP BY lb.sales_by_id
            ) b ON b.sales_by_id = u.id
            WHERE (u.role_id IN (5,8) OR (u.role_id = 9 AND u.team_id IS NOT NULL))
              AND (emp.employee_status IS NULL OR (emp.deleted_at IS NULL AND LOWER(TRIM(emp.employee_status)) NOT IN ('resigned', 'inactive', 'terminated')))
              AND (emp.employee_type IS NULL OR LOWER(TRIM(emp.employee_type)) NOT IN ('resigned', 'inactive', 'terminated'))
            ORDER BY b.completed DESC, u.full_name ASC
        ";
    }
    else {
        // Weekly / Monthly → per executive
        $sql = "
            SELECT 
                u.id,
                u.full_name AS name,
                IFNULL(st.booking_target,0) AS booking_target,
                IFNULL(b.completed,0) AS booking_completed,
                IFNULL(b.total_flat_value, 0) AS total_flat_value,
                IFNULL(b.total_booking_money, 0) AS total_booking_money,
                IFNULL(b.total_registration_money, 0) AS total_registration_money
            FROM users u
            LEFT JOIN employees emp ON (emp.user_id = u.id OR emp.id = u.employee_id)
            LEFT JOIN sales_targets st 
                ON st.sales_user_id = u.id
                AND $targetCondition
            LEFT JOIN (
                SELECT 
                    lb.sales_by_id, 
                    COUNT(DISTINCT lb.id) AS completed,
                    SUM(COALESCE(NULLIF(lb.agreement_value, 0), f.price_value, 0)) AS total_flat_value,
                    SUM(IFNULL(pay_b.booking_amount, 0)) AS total_booking_money,
                    SUM(IFNULL(pay_r.registration_amount, 0)) AS total_registration_money
                FROM lead_bookings lb
                LEFT JOIN flats f ON f.id = lb.flat_id
                LEFT JOIN (
                    SELECT booking_id, SUM(amount) AS booking_amount
                    FROM lead_payments
                    WHERE payment_type = 'booking_money'
                      AND payment_status IN ('paid', 'partially_paid')
                      AND verification_status = 'verified'
                    GROUP BY booking_id
                ) pay_b ON pay_b.booking_id = lb.id
                LEFT JOIN (
                    SELECT booking_id, SUM(amount) AS registration_amount
                    FROM lead_payments
                    WHERE payment_type = 'registration'
                      AND payment_status IN ('paid', 'partially_paid')
                      AND verification_status = 'verified'
                    GROUP BY booking_id
                ) pay_r ON pay_r.booking_id = lb.id
                WHERE lb.booking_status IN ('completed', 'booked')
                  AND lb.deleted_at IS NULL
                  AND $dateCondition
                  AND lb.sales_by_id IS NOT NULL
                GROUP BY lb.sales_by_id
            ) b ON b.sales_by_id = u.id
            WHERE (u.role_id IN (5,8) OR (u.role_id = 9 AND u.team_id IS NOT NULL))
              AND (emp.employee_status IS NULL OR (emp.deleted_at IS NULL AND LOWER(TRIM(emp.employee_status)) NOT IN ('resigned', 'inactive', 'terminated')))
              AND (emp.employee_type IS NULL OR LOWER(TRIM(emp.employee_type)) NOT IN ('resigned', 'inactive', 'terminated'))
            ORDER BY b.completed DESC, u.full_name ASC
        ";
    }

    // 🔹 Execute query
    $result = $this->db->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}


//for searching by sales team
public function getExecutiveFullStats($executiveId = null, $startDate = null, $endDate = null)
{
    if (empty($executiveId) || empty($startDate) || empty($endDate)) return [];

    $bookingWhere = ["lb.booking_status IN ('completed', 'booked')", "lb.sales_by_id = " . (int)$executiveId];
    $visitWhere   = ["lf.follow_up_type = 'visit'", "lf.status = 'completed'", "lf.assigned_with = " . (int)$executiveId];
    $targetWhere  = ["st.sales_user_id = " . (int)$executiveId];

    // Date range filter
    $safeStartDate = $this->db->real_escape_string($startDate);
    $safeEndDate   = $this->db->real_escape_string($endDate);

    $bookingWhere[] = "DATE(lb.booking_date) >= '$safeStartDate'";
    $bookingWhere[] = "DATE(lb.booking_date) <= '$safeEndDate'";

    $visitWhere[] = "DATE(lf.follow_up_date) >= '$safeStartDate'";
    $visitWhere[] = "DATE(lf.follow_up_date) <= '$safeEndDate'";

    // Target table has 'month' column which is likely a date format like 'Y-m-01'
    $targetWhere[] = "DATE(st.month) >= DATE_FORMAT('$safeStartDate', '%Y-%m-01')";
    $targetWhere[] = "DATE(st.month) <= LAST_DAY('$safeEndDate')";

    $bookingCondition = implode(" AND ", $bookingWhere);
    $visitCondition   = implode(" AND ", $visitWhere);
    $targetCondition  = implode(" AND ", $targetWhere);

 $sql = " SELECT 
        u.id,
        u.full_name,
        IFNULL(SUM(st.site_visit_target),0) AS site_visit_target,
        IFNULL(SUM(st.booking_target),0) AS booking_target,
        IFNULL(v.visit_completed,0) AS visit_completed,
        IFNULL(b.booking_completed,0) AS booking_completed,
        IFNULL(b.total_booking_money,0) AS total_booking_money,
        IFNULL(b.total_flat_value,0) AS total_flat_value
    FROM users u
    LEFT JOIN sales_targets st
        ON st.sales_user_id = u.id AND $targetCondition
    LEFT JOIN (
        SELECT assigned_with, COUNT(*) AS visit_completed
        FROM lead_follow_ups lf
        WHERE $visitCondition
        GROUP BY assigned_with
    ) v ON v.assigned_with = u.id
    LEFT JOIN (
        SELECT sales_by_id, COUNT(*) AS booking_completed,
            SUM(agreement_value - IFNULL(discount_amount,0)) AS total_booking_money,
            SUM(f.price_value) AS total_flat_value
        FROM lead_bookings lb
        LEFT JOIN flats f ON f.id = lb.flat_id
        WHERE $bookingCondition
        GROUP BY sales_by_id
    ) b ON b.sales_by_id = u.id
    WHERE u.id = " . (int)$executiveId . "
    GROUP BY 
        u.id,
        u.full_name,
        v.visit_completed,
        b.booking_completed,
        b.total_booking_money,
        b.total_flat_value
";



    $result = $this->db->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

    public function getRangeStats($userId, $year, $startMonth, $endMonth)
    {
        $cycleStart = $this->getCycleDateRange($year, $startMonth);
        $cycleEnd = $this->getCycleDateRange($year, $endMonth);
        $start = $cycleStart['start'];
        $end = $cycleEnd['end'];

        // 1. Targets (Targets are based on the 'month' column which acts as the cycle)
        $sql = "SELECT 
                    SUM(site_visit_target) AS site_visit_target,
                    SUM(booking_target) AS booking_target,
                    SUM(booking_value_target) AS booking_value_target
                FROM sales_targets
                WHERE sales_user_id = ?
                  AND month BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $targetStartMonth = sprintf('%04d-%02d-01', $year, $startMonth);
        $targetEndMonth = sprintf('%04d-%02d-01', $year, $endMonth);
        $stmt->bind_param("iss", $userId, $targetStartMonth, $targetEndMonth);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // 2. Visits Completed
        $sql = "SELECT COUNT(*) as completed 
                FROM lead_follow_ups
                WHERE assigned_with = ? 
                  AND follow_up_type = 'visit' 
                  AND status = 'completed'
                  AND follow_up_date BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iss", $userId, $start, $end);
        $stmt->execute();
        $visits = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // 3. Bookings Completed
        $sql = "SELECT COUNT(*) AS total
                FROM lead_bookings
                WHERE sales_by_id = ?
                  AND booking_status IN ('completed','booked')
                  AND booking_date BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iss", $userId, $start, $end);
        $stmt->execute();
        $bookings = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // 4. Deals Closed
        $sql = "SELECT COUNT(DISTINCT lf.lead_id) AS total
                FROM lead_follow_ups lf
                JOIN leads l ON l.id = lf.lead_id
                WHERE lf.assigned_with = ?
                  AND lf.follow_up_type = 'visit'
                  AND lf.status != 'canceled'
                  AND lf.follow_up_date BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iss", $userId, $start, $end);
        $stmt->execute();
        $dealsClosed = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // 5. Deals Won
        $sql = "SELECT COUNT(*) AS total
                FROM leads
                WHERE last_lead_action = 'booked'
                  AND assigned_id = ?
                  AND updated_at BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iss", $userId, $start, $end);
        $stmt->execute();
        $dealsWon = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // 6. Deals Lost
        $sql = "SELECT COUNT(*) AS total
                FROM leads l
                LEFT JOIN lead_follow_ups lf ON lf.lead_id = l.id
                WHERE l.assigned_id = ?
                  AND l.updated_at BETWEEN ? AND ?
                  AND (
                       l.last_lead_action = 'canceled'
                       OR (lf.follow_up_type = 'visit' AND lf.status = 'canceled')
                  )";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iss", $userId, $start, $end);
        $stmt->execute();
        $dealsLost = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return [
            'site_visit_target'    => (int)($target['site_visit_target'] ?? 0),
            'booking_target'       => (int)($target['booking_target'] ?? 0),
            'booking_value_target' => (float)($target['booking_value_target'] ?? 0.00),
            'visit_completed'      => (int)($visits['completed'] ?? 0),
            'booking_completed'    => (int)($bookings['total'] ?? 0),
            'deals_closed'         => (int)($dealsClosed['total'] ?? 0),
            'deals_won'            => (int)($dealsWon['total'] ?? 0),
            'deals_lost'           => (int)($dealsLost['total'] ?? 0),
        ];
    }

    public function getSalesTeamsBookingStatsByRange($year, $startMonth, $endMonth)
    {
        $sql = "
            SELECT 
                u.id,
                u.full_name AS name,
                IFNULL(st.total_target, 0) AS booking_target,
                IFNULL(b.completed, 0) AS booking_completed,
                IFNULL(b.total_flat_value, 0) AS total_flat_value,
                IFNULL(b.total_booking_money, 0) AS total_booking_money,
                IFNULL(b.total_registration_money, 0) AS total_registration_money
            FROM users u
            LEFT JOIN employees emp ON (emp.user_id = u.id OR emp.id = u.employee_id)
            LEFT JOIN (
                SELECT sales_user_id, SUM(booking_target) AS total_target
                FROM sales_targets
                WHERE YEAR(month) = ?
                  AND MONTH(month) >= ?
                  AND MONTH(month) <= ?
                GROUP BY sales_user_id
            ) st ON st.sales_user_id = u.id
            LEFT JOIN (
                SELECT 
                    lb.sales_by_id, 
                    COUNT(DISTINCT lb.id) AS completed,
                    SUM(COALESCE(NULLIF(lb.agreement_value, 0), f.price_value, 0)) AS total_flat_value,
                    SUM(IFNULL(pay_b.booking_amount, 0)) AS total_booking_money,
                    SUM(IFNULL(pay_r.registration_amount, 0)) AS total_registration_money
                FROM lead_bookings lb
                LEFT JOIN flats f ON f.id = lb.flat_id
                LEFT JOIN (
                    SELECT booking_id, SUM(amount) AS booking_amount
                    FROM lead_payments
                    WHERE payment_type = 'booking_money'
                      AND payment_status IN ('paid', 'partially_paid')
                      AND verification_status = 'verified'
                    GROUP BY booking_id
                ) pay_b ON pay_b.booking_id = lb.id
                LEFT JOIN (
                    SELECT booking_id, SUM(amount) AS registration_amount
                    FROM lead_payments
                    WHERE payment_type = 'registration'
                      AND payment_status IN ('paid', 'partially_paid')
                      AND verification_status = 'verified'
                    GROUP BY booking_id
                ) pay_r ON pay_r.booking_id = lb.id
                WHERE lb.booking_status IN ('completed', 'booked')
                  AND lb.deleted_at IS NULL
                  AND YEAR(lb.booking_date) = ?
                  AND MONTH(lb.booking_date) >= ?
                  AND MONTH(lb.booking_date) <= ?
                  AND lb.sales_by_id IS NOT NULL
                GROUP BY lb.sales_by_id
            ) b ON b.sales_by_id = u.id
            WHERE (u.role_id IN (5,8) OR (u.role_id = 9 AND u.team_id IS NOT NULL))
              AND (emp.employee_status IS NULL OR (emp.deleted_at IS NULL AND LOWER(TRIM(emp.employee_status)) NOT IN ('resigned', 'inactive', 'terminated')))
              AND (emp.employee_type IS NULL OR LOWER(TRIM(emp.employee_type)) NOT IN ('resigned', 'inactive', 'terminated'))
            ORDER BY b.completed DESC, u.full_name ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iiiiii", $year, $startMonth, $endMonth, $year, $startMonth, $endMonth);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }
}
