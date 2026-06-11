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
        $sql = "SELECT * FROM sales_targets 
            WHERE sales_user_id = ?
            ORDER BY month ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }


    /**
     * Insert or update target
     */
    public function saveTarget($userId, $month, $siteVisit, $bookingTarget, $bookingValue)
    {
        $sql = "
            INSERT INTO sales_targets
            (sales_user_id, month, site_visit_target, booking_target, booking_value_target)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                site_visit_target = VALUES(site_visit_target),
                booking_target = VALUES(booking_target),
                booking_value_target = VALUES(booking_value_target)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "isiii",
            $userId,
            $month,
            $siteVisit,
            $bookingTarget,
            $bookingValue
        );
        return $stmt->execute();
    }

    public function getTargetProgress($userId, $month, $year)
    {
        // Booking
        $sql1 = "SELECT COUNT(*) AS deals_closed, 
                    SUM(agreement_value - IFNULL(discount_amount,0)) AS total_value
             FROM lead_bookings
             WHERE sales_by_id = ? AND booking_status = 'completed' 
                   AND MONTH(booking_date) = ? AND YEAR(booking_date) = ?";
        $stmt1 = $this->db->prepare($sql1);
        $stmt1->bind_param("iii", $userId, $month, $year);
        $stmt1->execute();
        $booking = $stmt1->get_result()->fetch_assoc();

        // Visits
        $sql2 = "SELECT COUNT(*) AS visits_done
             FROM lead_follow_ups
             WHERE assigned_with = ? AND follow_up_type='visit' 
                   AND status='completed' AND MONTH(follow_up_date)=? 
                   AND YEAR(follow_up_date)=?";
        $stmt2 = $this->db->prepare($sql2);
        $stmt2->bind_param("iii", $userId, $month, $year);
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
        $sql = "SELECT COUNT(*) as completed FROM lead_follow_ups
                WHERE assigned_with=? AND follow_up_type='visit' AND status='completed'
                  AND DATE_FORMAT(follow_up_date,'%Y-%m')=?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $userId, $month);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['completed'] ?? 0;
    }

    // Get completed bookings for a month
    public function getMonthlyBookingCompleted($userId, $month)
    {
        $sql = "
        SELECT COUNT(*) AS total
        FROM lead_bookings
        WHERE sales_by_id = ?
          AND DATE_FORMAT(booking_date, '%Y-%m') = ?
          AND booking_status IN ('completed','booked')
    ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $userId, $month);
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
        $sql = "
        SELECT COUNT(DISTINCT lf.lead_id) AS total
        FROM lead_follow_ups lf
        JOIN leads l ON l.id = lf.lead_id
        WHERE lf.assigned_with = ?
          AND DATE_FORMAT(lf.follow_up_date, '%Y-%m') = ?
          AND lf.follow_up_type = 'visit'
          AND lf.status != 'canceled'
    ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $userId, $month);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int)($result['total'] ?? 0);
    }

    public function getDealsWon($userId, $month)
    {
        $sql = "
        SELECT COUNT(*) AS total
        FROM leads
        WHERE last_lead_action = 'booked'
          AND assigned_id  = ?
          AND DATE_FORMAT(updated_at, '%Y-%m') = ?
    ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $userId, $month);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int)($result['total'] ?? 0);
    }

    public function getDealsLost($userId, $month)
    {
        $sql = "
        SELECT COUNT(*) AS total
        FROM leads l
        LEFT JOIN lead_follow_ups lf ON lf.lead_id = l.id
        WHERE l.assigned_id = ?
          AND DATE_FORMAT(l.updated_at, '%Y-%m') = ?
          AND (
               l.last_lead_action = 'canceled'
               OR (lf.follow_up_type = 'visit' AND lf.status = 'canceled')
          )
    ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $userId, $month);
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
        // Default → monthly
        $dateCondition   = "DATE_FORMAT(lb.booking_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
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
            LEFT JOIN (
                SELECT sales_user_id, SUM(booking_target) AS total_target
                FROM sales_targets
                GROUP BY sales_user_id
            ) st ON st.sales_user_id = u.id
            INNER JOIN (
                SELECT 
                    lb.sales_by_id, 
                    COUNT(DISTINCT lb.id) AS completed,
                    SUM(f.price_value) AS total_flat_value,
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
            WHERE u.role_id IN (5,8)
            ORDER BY u.full_name ASC
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
            LEFT JOIN sales_targets st 
                ON st.sales_user_id = u.id
                AND $targetCondition
            INNER JOIN (
                SELECT 
                    lb.sales_by_id, 
                    COUNT(DISTINCT lb.id) AS completed,
                    SUM(f.price_value) AS total_flat_value,
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
            WHERE u.role_id IN (5,8)
            ORDER BY u.full_name ASC
        ";
    }

    // 🔹 Execute query
    $result = $this->db->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}


//for searching by sales team
public function getExecutiveFullStats($executiveId = null, $startMonth = null, $endMonth = null, $year = null)
{
    if (empty($executiveId) || empty($year)) return [];

    $bookingWhere = ["lb.booking_status IN ('completed', 'booked')", "lb.sales_by_id = " . (int)$executiveId];
    $visitWhere   = ["lf.follow_up_type = 'visit'", "lf.status = 'completed'", "lf.assigned_with = " . (int)$executiveId];
    $targetWhere  = ["st.sales_user_id = " . (int)$executiveId];

    // Year filter
    $bookingWhere[] = "YEAR(lb.booking_date) = " . (int)$year;
    $visitWhere[]   = "YEAR(lf.follow_up_date) = " . (int)$year;
    $targetWhere[]  = "YEAR(st.month) = " . (int)$year;

    // Month range filter (optional)
    if (!empty($startMonth)) {
        $bookingWhere[] = "MONTH(lb.booking_date) >= " . (int)$startMonth;
        $visitWhere[]   = "MONTH(lf.follow_up_date) >= " . (int)$startMonth;
        $targetWhere[]  = "MONTH(st.month) >= " . (int)$startMonth;
    }
    if (!empty($endMonth)) {
        $bookingWhere[] = "MONTH(lb.booking_date) <= " . (int)$endMonth;
        $visitWhere[]   = "MONTH(lf.follow_up_date) <= " . (int)$endMonth;
        $targetWhere[]  = "MONTH(st.month) <= " . (int)$endMonth;
    }

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
        // 1. Targets
        $sql = "SELECT 
                    SUM(site_visit_target) AS site_visit_target,
                    SUM(booking_target) AS booking_target,
                    SUM(booking_value_target) AS booking_value_target
                FROM sales_targets
                WHERE sales_user_id = ?
                  AND YEAR(month) = ?
                  AND MONTH(month) >= ?
                  AND MONTH(month) <= ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iiii", $userId, $year, $startMonth, $endMonth);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // 2. Visits Completed
        $sql = "SELECT COUNT(*) as completed 
                FROM lead_follow_ups
                WHERE assigned_with = ? 
                  AND follow_up_type = 'visit' 
                  AND status = 'completed'
                  AND YEAR(follow_up_date) = ?
                  AND MONTH(follow_up_date) >= ?
                  AND MONTH(follow_up_date) <= ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iiii", $userId, $year, $startMonth, $endMonth);
        $stmt->execute();
        $visits = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // 3. Bookings Completed
        $sql = "SELECT COUNT(*) AS total
                FROM lead_bookings
                WHERE sales_by_id = ?
                  AND booking_status IN ('completed','booked')
                  AND YEAR(booking_date) = ?
                  AND MONTH(booking_date) >= ?
                  AND MONTH(booking_date) <= ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iiii", $userId, $year, $startMonth, $endMonth);
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
                  AND YEAR(lf.follow_up_date) = ?
                  AND MONTH(lf.follow_up_date) >= ?
                  AND MONTH(lf.follow_up_date) <= ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iiii", $userId, $year, $startMonth, $endMonth);
        $stmt->execute();
        $dealsClosed = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // 5. Deals Won
        $sql = "SELECT COUNT(*) AS total
                FROM leads
                WHERE last_lead_action = 'booked'
                  AND assigned_id = ?
                  AND YEAR(updated_at) = ?
                  AND MONTH(updated_at) >= ?
                  AND MONTH(updated_at) <= ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iiii", $userId, $year, $startMonth, $endMonth);
        $stmt->execute();
        $dealsWon = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // 6. Deals Lost
        $sql = "SELECT COUNT(*) AS total
                FROM leads l
                LEFT JOIN lead_follow_ups lf ON lf.lead_id = l.id
                WHERE l.assigned_id = ?
                  AND YEAR(l.updated_at) = ?
                  AND MONTH(l.updated_at) >= ?
                  AND MONTH(l.updated_at) <= ?
                  AND (
                       l.last_lead_action = 'canceled'
                       OR (lf.follow_up_type = 'visit' AND lf.status = 'canceled')
                  )";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iiii", $userId, $year, $startMonth, $endMonth);
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
            LEFT JOIN (
                SELECT sales_user_id, SUM(booking_target) AS total_target
                FROM sales_targets
                WHERE YEAR(month) = ?
                  AND MONTH(month) >= ?
                  AND MONTH(month) <= ?
                GROUP BY sales_user_id
            ) st ON st.sales_user_id = u.id
            INNER JOIN (
                SELECT 
                    lb.sales_by_id, 
                    COUNT(DISTINCT lb.id) AS completed,
                    SUM(f.price_value) AS total_flat_value,
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
            WHERE u.role_id IN (5,8)
            ORDER BY u.full_name ASC
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
