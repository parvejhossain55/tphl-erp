<?php

class BookingLead extends Model
{
    public $db;

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db; // mysqli connection
    }

    // Get all follow-ups with related lead, project, flat, and creator info
    
    public function countBookingsByLeadId($leadId)
    {
        $sql = "SELECT COUNT(*) as total FROM lead_bookings WHERE lead_id = ? AND booking_status IN ('booked', 'completed') AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return 0;
        $stmt->bind_param("i", $leadId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row ? (int)$row['total'] : 0;
    }

    public function getAllOnBoard()
    {
        $sql = "SELECT lb.*, 
       l.full_name, 
       p.name AS project_name, 
       f.flat_name AS flat_name, 
       u1.full_name AS created_by_name,
       lb.booking_date as booking_pay_date,
       COALESCE(u_sales.full_name, lb.sales_agent_old) AS sales_agent_display
FROM lead_bookings lb
LEFT JOIN leads l ON lb.lead_id = l.id
LEFT JOIN flats f ON lb.flat_id = f.id
LEFT JOIN projects p ON f.project_id = p.id
LEFT JOIN users u1 ON lb.created_by = u1.id
LEFT JOIN users u_sales ON lb.sales_by_id = u_sales.id
WHERE lb.deleted_at IS NULL
ORDER BY lb.follow_up_date DESC, lb.follow_up_time ASC
";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getAllBooking($year = null, $month = null, $project_id = null, $teamId = null, $from_date = null, $to_date = null, $sales_by_id = null)
    {
        return $this->getAllOnboardClientBooking($year, $month, $project_id, $teamId, $from_date, $to_date, $sales_by_id);
    }

    public function getAllBookings($year = null, $month = null, $project_id = null, $teamId = null, $from_date = null, $to_date = null, $sales_by_id = null)
    {
        return $this->getAllOnboardClientBooking($year, $month, $project_id, $teamId, $from_date, $to_date, $sales_by_id);
    }




    public function getAllOnboardClientBooking($year = null, $month = null, $project_id = null, $teamId = null, $from_date = null, $to_date = null, $sales_by_id = null)
    {
        $sql = "SELECT 
                lb.*, 
                l.full_name, 
                l.id as lead_id,
                COALESCE(NULLIF(l.phone, ''), l.phone_number) AS phone,
                l.email,
                p.name AS project_name, 
                f.flat_name AS flat_name, 
                f.sqft,
                f.price_value,
                u1.full_name AS created_by_name,
                COALESCE(lb.booking_date, lp_sum.min_created_at) As booking_pay_date,
                IFNULL(lp_sum.payment_method, '') AS payment_method,
                IFNULL(lp_sum.booking_money, 0) AS booking_money,
                IFNULL(lp_sum.registration_money, 0) AS registration_money,
                IFNULL(lp_sum.amount, 0) AS amount,
                COALESCE(u_sales.full_name, lb.sales_agent_old) AS sales_agent_display,
                COALESCE(lb.team_id, l.team_id) AS sale_team_id,
                t_sale.name AS sale_team_name,
                t_sale.name AS team_name,
                COALESCE(NULLIF(e_sales.team_id, 0), NULLIF(u_sales.team_id, 0)) AS current_team_id,
                t_curr.name AS current_team_name,
                e_sales.employee_status AS sales_employee_status
            FROM lead_bookings lb
            LEFT JOIN leads l ON lb.lead_id = l.id
            LEFT JOIN flats f ON lb.flat_id = f.id
            LEFT JOIN projects p ON f.project_id = p.id
            LEFT JOIN users u1 ON lb.created_by = u1.id
            LEFT JOIN users u_sales ON lb.sales_by_id = u_sales.id
            LEFT JOIN employees e_sales ON (e_sales.user_id = u_sales.id OR (u_sales.employee_id IS NOT NULL AND e_sales.id = u_sales.employee_id))
            LEFT JOIN teams t_sale ON t_sale.id = COALESCE(lb.team_id, l.team_id)
            LEFT JOIN teams t_curr ON t_curr.id = COALESCE(NULLIF(e_sales.team_id, 0), NULLIF(u_sales.team_id, 0))
            LEFT JOIN (
                SELECT 
                    booking_id,
                    MAX(payment_method) AS payment_method,
                    MIN(created_at) AS min_created_at,
                    SUM(CASE WHEN payment_type = 'booking_money' THEN IFNULL(amount, 0) ELSE 0 END) AS booking_money,
                    SUM(CASE WHEN payment_type = 'registration' THEN IFNULL(amount, 0) ELSE 0 END) AS registration_money,
                    SUM(IFNULL(amount, 0)) AS amount
                FROM lead_payments
                WHERE payment_status = 'paid'
                GROUP BY booking_id
            ) AS lp_sum ON lp_sum.booking_id = lb.id
            WHERE lb.deleted_at IS NULL
              AND l.deleted_at IS NULL
              AND lb.booking_status IN ('booked', 'completed')
              AND lb.id NOT IN (
                  SELECT booking_id FROM client_refund_requests WHERE status IN ('pending', 'approved', 'refunded')
              )
             ";

        $params = [];
        $types  = "";

        // Custom date range takes priority over year/month
        if (!empty($from_date) && !empty($to_date)) {
            $sql .= " AND (DATE(lb.booking_date) BETWEEN ? AND ? OR (lb.booking_date IS NULL AND DATE(lb.created_at) BETWEEN ? AND ?))";
            $params[] = $from_date;
            $params[] = $to_date;
            $params[] = $from_date;
            $params[] = $to_date;
            $types   .= "ssss";
        } elseif (!empty($from_date)) {
            $sql .= " AND (DATE(lb.booking_date) >= ? OR (lb.booking_date IS NULL AND DATE(lb.created_at) >= ?))";
            $params[] = $from_date;
            $params[] = $from_date;
            $types   .= "ss";
        } elseif (!empty($to_date)) {
            $sql .= " AND (DATE(lb.booking_date) <= ? OR (lb.booking_date IS NULL AND DATE(lb.created_at) <= ?))";
            $params[] = $to_date;
            $params[] = $to_date;
            $types   .= "ss";
        } elseif (!empty($year) || !empty($month)) {
            if (!empty($year)) {
                $sql .= " AND (YEAR(lb.booking_date) = ? OR (lb.booking_date IS NULL AND YEAR(lb.created_at) = ?))";
                $params[] = (int)$year;
                $params[] = (int)$year;
                $types   .= "ii";
            }
            if (!empty($month)) {
                $sql .= " AND (MONTH(lb.booking_date) = ? OR (lb.booking_date IS NULL AND MONTH(lb.created_at) = ?))";
                $params[] = (int)$month;
                $params[] = (int)$month;
                $types   .= "ii";
            }
        }

        if (!empty($project_id)) {
            $sql .= " AND lb.project_id = ?";
            $params[] = (int)$project_id;
            $types   .= "i";
        }

        // Team filter (strictly scopes to the given team or array of teams)
        if (is_array($teamId)) {
            $cleanIds = array_values(array_filter(array_map('intval', $teamId), fn($v) => $v > 0));
            if (empty($cleanIds)) {
                $sql .= " AND 1=0";
            } else {
                $sql .= " AND COALESCE(lb.team_id, l.team_id) IN (" . implode(',', $cleanIds) . ")";
            }
        } elseif (!empty($teamId)) {
            $teamIdInt = (int)$teamId;
            if ($teamIdInt < 0) {
                $sql .= " AND 1=0";
            } else {
                $sql .= " AND COALESCE(lb.team_id, l.team_id) = {$teamIdInt}";
            }
        }

        // Salesperson filter
        if (!empty($sales_by_id)) {
            $sql .= " AND (lb.sales_by_id = ? OR (lb.sales_by_id IS NULL AND l.assigned_id = ?))";
            $params[] = (int)$sales_by_id;
            $params[] = (int)$sales_by_id;
            $types   .= "ii";
        }

        $sql .= " ORDER BY lb.booking_date DESC, lb.id DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            return [];
        }

        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get unique salespersons and their active onboarding client counts
     * Used for the Sales Executive filter dropdown and pill counts
     */
    public function getOnboardSalespersonCounts($teamId = null, $year = null, $month = null, $project_id = null, $from_date = null, $to_date = null)
    {
        $sql = "SELECT 
                    COALESCE(u.id, u_assigned.id, 0) AS user_id,
                    COALESCE(u.full_name, u_assigned.full_name, lb.sales_agent_old, 'Unknown Sales Person') AS sales_name,
                    COUNT(DISTINCT lb.id) AS client_count
                FROM lead_bookings lb
                JOIN leads l ON l.id = lb.lead_id
                LEFT JOIN users u ON u.id = lb.sales_by_id
                LEFT JOIN users u_assigned ON u_assigned.id = l.assigned_id
                WHERE lb.deleted_at IS NULL AND l.deleted_at IS NULL
                  AND lb.booking_status IN ('booked', 'completed')
                  AND lb.booking_status NOT IN ('cancelled', 'refunded')
                  AND lb.id NOT IN (
                      SELECT booking_id FROM client_refund_requests WHERE status IN ('pending', 'approved', 'refunded')
                  )";

        $params = [];
        $types  = "";

        if (is_array($teamId)) {
            $cleanIds = array_values(array_filter(array_map('intval', $teamId), fn($v) => $v > 0));
            if (empty($cleanIds)) {
                $sql .= " AND 1=0";
            } else {
                $sql .= " AND COALESCE(lb.team_id, l.team_id) IN (" . implode(',', $cleanIds) . ")";
            }
        } elseif (!empty($teamId)) {
            $teamIdInt = (int)$teamId;
            if ($teamIdInt < 0) {
                $sql .= " AND 1=0";
            } else {
                $sql .= " AND COALESCE(lb.team_id, l.team_id) = ?";
                $params[] = $teamIdInt;
                $types   .= "i";
            }
        }

        if (!empty($project_id)) {
            $sql .= " AND lb.project_id = ?";
            $params[] = (int)$project_id;
            $types   .= "i";
        }

        if (!empty($from_date) && !empty($to_date)) {
            $sql .= " AND (DATE(lb.booking_date) BETWEEN ? AND ? OR (lb.booking_date IS NULL AND DATE(lb.created_at) BETWEEN ? AND ?))";
            $params[] = $from_date;
            $params[] = $to_date;
            $params[] = $from_date;
            $params[] = $to_date;
            $types   .= "ssss";
        } elseif (!empty($from_date)) {
            $sql .= " AND (DATE(lb.booking_date) >= ? OR (lb.booking_date IS NULL AND DATE(lb.created_at) >= ?))";
            $params[] = $from_date;
            $params[] = $from_date;
            $types   .= "ss";
        } elseif (!empty($to_date)) {
            $sql .= " AND (DATE(lb.booking_date) <= ? OR (lb.booking_date IS NULL AND DATE(lb.created_at) <= ?))";
            $params[] = $to_date;
            $params[] = $to_date;
            $types   .= "ss";
        } elseif (!empty($year) || !empty($month)) {
            if (!empty($year)) {
                $sql .= " AND (YEAR(lb.booking_date) = ? OR (lb.booking_date IS NULL AND YEAR(lb.created_at) = ?))";
                $params[] = (int)$year;
                $params[] = (int)$year;
                $types   .= "ii";
            }
            if (!empty($month)) {
                $sql .= " AND (MONTH(lb.booking_date) = ? OR (lb.booking_date IS NULL AND MONTH(lb.created_at) = ?))";
                $params[] = (int)$month;
                $params[] = (int)$month;
                $types   .= "ii";
            }
        }

        $sql .= " GROUP BY user_id, sales_name ORDER BY client_count DESC, sales_name ASC";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $res = $stmt->get_result();
                $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
                $stmt->close();
                return $rows;
            }
        } else {
            $res = $this->db->query($sql);
            return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        }

        return [];
    }

    public function getAllOnboardClient($year = null, $month = null)
    {
        $sql = "SELECT 
                l.id AS lead_id, 
                l.full_name, 
                l.email, 
                COALESCE(NULLIF(l.phone, ''), l.phone_number) AS phone,
                MIN(lb.id) AS lead_booking_id
            FROM lead_bookings lb
            LEFT JOIN leads l ON lb.lead_id = l.id
            LEFT JOIN onboard_clients oc ON oc.lead_id = lb.lead_id
            WHERE lb.deleted_at IS NULL
              AND lb.booking_status IN ('booked', 'completed')
              AND oc.id IS NULL";

        $params = [];
        $types = "";

        if (!empty($year)) {
            $sql .= " AND YEAR(lb.booking_date) = ?";
            $params[] = (int)$year;
            $types .= "i";
        }

        if (!empty($month)) {
            $sql .= " AND MONTH(lb.booking_date) = ?";
            $params[] = (int)$month;
            $types .= "i";
        }

        $sql .= " GROUP BY l.id, l.full_name, phone, l.email
              ORDER BY l.full_name ASC";

        $stmt = $this->db->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }



    public function getAllMyOnboardProject($userId, $year = null, $month = null, $from_date = null, $to_date = null)
    {
        $sql = "SELECT 
            lb.*, 
            l.full_name, 
            l.id AS lead_id,
            COALESCE(NULLIF(l.phone, ''), l.phone_number) AS phone,
            l.email,
            p.name AS project_name, 
            f.flat_name AS flat_name, 
            f.sqft,
            f.price_value,
            u1.full_name AS created_by_name,
            COALESCE(lb.booking_date, lp_sum.min_created_at) AS booking_pay_date,
            IFNULL(lp_sum.payment_method, '') AS payment_method,
            IFNULL(lp_sum.booking_money, 0) AS booking_money,
            IFNULL(lp_sum.registration_money, 0) AS registration_money,
            IFNULL(lp_sum.amount, 0) AS amount
        FROM lead_bookings lb
        LEFT JOIN leads l ON lb.lead_id = l.id
        LEFT JOIN flats f ON lb.flat_id = f.id
        LEFT JOIN projects p ON f.project_id = p.id
        LEFT JOIN users u1 ON lb.created_by = u1.id
        LEFT JOIN (
            SELECT 
                booking_id,
                MAX(payment_method) AS payment_method,
                MIN(created_at) AS min_created_at,
                SUM(CASE WHEN payment_type = 'booking_money' THEN IFNULL(amount, 0) ELSE 0 END) AS booking_money,
                SUM(CASE WHEN payment_type = 'registration' THEN IFNULL(amount, 0) ELSE 0 END) AS registration_money,
                SUM(IFNULL(amount, 0)) AS amount
            FROM lead_payments
            WHERE payment_status = 'paid'
            GROUP BY booking_id
        ) AS lp_sum ON lp_sum.booking_id = lb.id
        WHERE lb.deleted_at IS NULL
          AND (l.deleted_at IS NULL OR l.id IS NULL)
          AND lb.booking_status IN ('booked', 'completed')
          AND lb.booking_status NOT IN ('cancelled', 'refunded')
          AND lb.id NOT IN (
              SELECT booking_id FROM client_refund_requests WHERE status IN ('pending', 'approved', 'refunded')
          )
          AND (lb.sales_by_id = ? OR (lb.sales_by_id IS NULL AND l.assigned_id = ?))";

        $params = [(int)$userId, (int)$userId];
        $types  = "ii";

        // Custom date range takes priority over year/month
        if (!empty($from_date) && !empty($to_date)) {
            $sql .= " AND (DATE(lb.booking_date) BETWEEN ? AND ? OR (lb.booking_date IS NULL AND DATE(lb.created_at) BETWEEN ? AND ?))";
            $params[] = $from_date;
            $params[] = $to_date;
            $params[] = $from_date;
            $params[] = $to_date;
            $types   .= "ssss";
        } elseif (!empty($from_date)) {
            $sql .= " AND (DATE(lb.booking_date) >= ? OR (lb.booking_date IS NULL AND DATE(lb.created_at) >= ?))";
            $params[] = $from_date;
            $params[] = $from_date;
            $types   .= "ss";
        } elseif (!empty($to_date)) {
            $sql .= " AND (DATE(lb.booking_date) <= ? OR (lb.booking_date IS NULL AND DATE(lb.created_at) <= ?))";
            $params[] = $to_date;
            $params[] = $to_date;
            $types   .= "ss";
        } elseif (!empty($year) || !empty($month)) {
            if (!empty($year)) {
                $sql .= " AND (YEAR(lb.booking_date) = ? OR (lb.booking_date IS NULL AND YEAR(lb.created_at) = ?))";
                $params[] = (int)$year;
                $params[] = (int)$year;
                $types   .= "ii";
            }
            if (!empty($month)) {
                $sql .= " AND (MONTH(lb.booking_date) = ? OR (lb.booking_date IS NULL AND MONTH(lb.created_at) = ?))";
                $params[] = (int)$month;
                $params[] = (int)$month;
                $types   .= "ii";
            }
        }

        $sql .= " ORDER BY lb.booking_date DESC, lb.id DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            return [];
        }

        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function getAllAssignedLeads($userId, $year = null, $month = null)
    {
        $sql = "SELECT 
                lb.*, 
                l.full_name, 
                l.id AS lead_id,
                COALESCE(NULLIF(l.phone, ''), l.phone_number) AS phone,
                l.email,
                p.name AS project_name, 
                f.flat_name,
                f.sqft,
                f.price_value,
                u1.full_name AS created_by_name,
                COALESCE(lb.booking_date, lp_sum.min_created_at) AS booking_pay_date,
                IFNULL(lp_sum.payment_method, '') AS payment_method,
                IFNULL(lp_sum.booking_money, 0) AS booking_money,
                IFNULL(lp_sum.registration_money, 0) AS registration_money,
                IFNULL(lp_sum.amount, 0) AS amount
            FROM lead_bookings lb
            LEFT JOIN leads l ON lb.lead_id = l.id
            LEFT JOIN flats f ON lb.flat_id = f.id
            LEFT JOIN projects p ON f.project_id = p.id
            LEFT JOIN users u1 ON lb.created_by = u1.id
            LEFT JOIN (
                SELECT 
                    booking_id,
                    MAX(payment_method) AS payment_method,
                    MIN(created_at) AS min_created_at,
                    SUM(CASE WHEN payment_type = 'booking_money' THEN IFNULL(amount, 0) ELSE 0 END) AS booking_money,
                    SUM(CASE WHEN payment_type = 'registration' THEN IFNULL(amount, 0) ELSE 0 END) AS registration_money,
                    SUM(IFNULL(amount, 0)) AS amount
                FROM lead_payments
                GROUP BY booking_id
            ) AS lp_sum ON lp_sum.booking_id = lb.id
            WHERE lb.deleted_at IS NULL
              AND lb.sales_by_id = ?";

        $params = [(int)$userId];
        $types  = "i";

        // Year filter (booking date based)
        if (!empty($year)) {
            $sql .= " AND YEAR(lb.booking_date) = ?";
            $params[] = (int)$year;
            $types .= "i";
        }

        // Month filter (booking date based)
        if (!empty($month)) {
            $sql .= " AND MONTH(lb.booking_date) = ?";
            $params[] = (int)$month;
            $types .= "i";
        }

        $sql .= " ORDER BY lb.booking_date DESC, lb.id DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            return [];
        }

        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    //dashboard
    public function getTotalBookings($period = null, $strictMode = false)
    {
        if ($strictMode) {
            $sqlQuery = "SELECT COUNT(*) AS onboarded 
                     FROM lead_bookings lb
                     INNER JOIN leads l ON lb.lead_id = l.id
                     WHERE lb.booking_status IN ('booked', 'completed') 
                     AND lb.deleted_at IS NULL
                     AND l.deleted_at IS NULL
                     AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
                     AND lb.id NOT IN (
                         SELECT crr.booking_id FROM client_refund_requests crr
                         WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded')
                     )";
            $dateCol = "lb.created_at";
        } else {
            $sqlQuery = "SELECT COUNT(*) AS onboarded 
                     FROM lead_bookings 
                     WHERE booking_status IN ('booked', 'completed') 
                     AND deleted_at IS NULL";
            $dateCol = "created_at";
        }

        if ($period && $period !== 'all_time') {
            switch ($period) {
                case 'daily':
                    $sqlQuery .= " AND DATE({$dateCol}) = CURDATE()";
                    break;
                case 'weekly':
                    if ($strictMode) {
                        $start = date('Y-m-d', strtotime('monday this week'));
                        $end = date('Y-m-d', strtotime('sunday this week'));
                        $sqlQuery .= " AND DATE({$dateCol}) BETWEEN '{$start}' AND '{$end}'";
                    } else {
                        $sqlQuery .= " AND {$dateCol} >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    }
                    break;
                case 'monthly':
                    if ($strictMode) {
                        $start = date('Y-m-01');
                        $end = date('Y-m-t');
                        $sqlQuery .= " AND DATE({$dateCol}) BETWEEN '{$start}' AND '{$end}'";
                    } else {
                        $sqlQuery .= " AND {$dateCol} >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                    }
                    break;
                case 'yearly':
                    if ($strictMode) {
                        $start = date('Y-01-01');
                        $end = date('Y-12-31');
                        $sqlQuery .= " AND DATE({$dateCol}) BETWEEN '{$start}' AND '{$end}'";
                    } else {
                        $sqlQuery .= " AND {$dateCol} >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                    }
                    break;
                case 'custom':
                    $fromDate = $_GET['from_date'] ?? '1970-01-01';
                    $toDate = $_GET['to_date'] ?? '2099-12-31';
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
                        $sqlQuery .= " AND DATE({$dateCol}) >= '{$fromDate}' AND DATE({$dateCol}) <= '{$toDate}'";
                    }
                    break;
            }
        }

        $result = $this->db->query($sqlQuery);
        $row = $result ? $result->fetch_assoc() : null;
        return (int)($row['onboarded'] ?? 0);
    }

    public function createFromCsvBulk($data)
    {
        $sql = "INSERT INTO lead_bookings (
            lead_id, project_id, flat_id, agreement_value, discount_amount, 
            booking_date, booking_status, created_by, sales_by_id, team_id, sales_agent_old, 
            approval_status, approved_amount, service_fee_balance, landshare_status, 
            payment_status, attachment_image, uploaded_from
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, 
            ?, ?, ?
        )";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new Exception($this->db->error);

        $v = function($key) use ($data) {
            return (isset($data[$key]) && trim($data[$key]) !== '') ? trim($data[$key]) : null;
        };

        $v_lead_id = $v('lead_id'); $v_project_id = $v('project_id'); $v_flat_id = $v('flat_id'); $v_agreement_value = $v('agreement_value'); $v_discount_amount = $v('discount_amount');
        $v_booking_date = $v('booking_date'); $v_booking_status = $v('booking_status'); $v_created_by = $v('created_by'); $v_sales_by_id = $v('sales_by_id'); $v_team_id = $v('team_id'); $v_sales_agent_old = $v('sales_agent_old');
        $v_approval_status = $v('approval_status'); $v_approved_amount = $v('approved_amount'); $v_service_fee_balance = $v('service_fee_balance'); $v_landshare_status = $v('landshare_status');
        $v_payment_status = $v('payment_status'); $v_attachment_image = $v('attachment_image');
        $v_uploaded_from = $data['uploaded_from'] ?? 'From Company csv';

        $bindStr = str_repeat('s', 18);
        $stmt->bind_param(
            $bindStr,
            $v_lead_id, $v_project_id, $v_flat_id, $v_agreement_value, $v_discount_amount,
            $v_booking_date, $v_booking_status, $v_created_by, $v_sales_by_id, $v_team_id, $v_sales_agent_old,
            $v_approval_status, $v_approved_amount, $v_service_fee_balance, $v_landshare_status,
            $v_payment_status, $v_attachment_image, $v_uploaded_from
        );

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        $bookingId = $this->db->insert_id;
        
        $v_flat_id = $data['flat_id'] ?? null;
        if (!empty($v_flat_id)) {
            $flatId = (int)$v_flat_id;
            $this->db->query("UPDATE flats SET is_booked = 1, status = 'booked' WHERE id = {$flatId}");
        }
        
        return $bookingId;
    }

    public function create($data)
    {
        $discountAmount = isset($data['discount_amount']) && $data['discount_amount'] !== ''
            ? (float) $data['discount_amount']
            : null;

        // 2 approval_status (ENUM-safe)
        $allowedStatuses = ['not_required', 'pending', 'approved', 'rejected'];
        $approvalStatus = $data['approval_status'] ?? 'not_required';

        // sanitize: match ENUM exactly
        if (!in_array(strtolower($approvalStatus), $allowedStatuses)) {
            $approvalStatus = 'not_required';
        } else {
            $approvalStatus = strtolower($approvalStatus);
        }

        // 3 SQL
        $salesAgentOld = $data['sales_agent_old'] ?? null;
        $pastApprovalAttachment = $data['past_approval_attachment'] ?? null;
        $approvedAmount = isset($data['approved_amount']) && $data['approved_amount'] !== '' ? (float)$data['approved_amount'] : null;

        // Resolve team_id for this booking if not explicitly provided
        $bookingTeamId = !empty($data['team_id']) ? (int)$data['team_id'] : null;
        if (empty($bookingTeamId) && !empty($data['sales_by_id'])) {
            $sId = (int)$data['sales_by_id'];
            $tStmt = $this->db->query("
                SELECT COALESCE(NULLIF(e.team_id, 0), NULLIF(u.team_id, 0)) AS resolved_tid
                FROM users u
                LEFT JOIN employees e ON (e.user_id = u.id OR (u.employee_id IS NOT NULL AND e.id = u.employee_id))
                WHERE u.id = {$sId} LIMIT 1
            ");
            if ($tStmt && $tRow = $tStmt->fetch_assoc()) {
                $bookingTeamId = !empty($tRow['resolved_tid']) ? (int)$tRow['resolved_tid'] : null;
            }
        }
        if (empty($bookingTeamId) && !empty($data['lead_id'])) {
            $lId = (int)$data['lead_id'];
            $lStmt = $this->db->query("SELECT team_id FROM leads WHERE id = {$lId} LIMIT 1");
            if ($lStmt && $lRow = $lStmt->fetch_assoc()) {
                $bookingTeamId = !empty($lRow['team_id']) ? (int)$lRow['team_id'] : null;
            }
        }

        $sql = "
        INSERT INTO lead_bookings
        (
            lead_id,
            project_id,
            flat_id,
            created_by,
            agreement_value,
            booking_date,
            booking_status,
            discount_amount,
            approval_status,
            approved_amount,
            past_approval_attachment,
            sales_by_id,
            team_id,
            sales_agent_old
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception($this->db->error);
        }

        // 4 Bind params
        $stmt->bind_param(
            "iiiidssdsdsiis",
            $data['lead_id'],         // 1: i
            $data['project_id'],      // 2: i
            $data['flat_id'],         // 3: i
            $data['created_by'],      // 4: i
            $data['agreement_value'], // 5: d
            $data['booking_date'],    // 6: s
            $data['booking_status'],  // 7: s
            $discountAmount,          // 8: d
            $approvalStatus,          // 9: s
            $approvedAmount,          // 10: d
            $pastApprovalAttachment,  // 11: s
            $data['sales_by_id'],     // 12: i
            $bookingTeamId,           // 13: i
            $salesAgentOld            // 14: s
        );

        // 5 Execute
        if ($stmt->execute()) {
            $bookingId = $this->db->insert_id;
            if (!empty($data['flat_id'])) {
                $flatId = (int)$data['flat_id'];
                $this->db->query("UPDATE flats SET is_booked = 1, status = 'booked' WHERE id = {$flatId}");
            }
            return $bookingId;
        }

        throw new Exception($stmt->error);
    }


    /**
     * Get the final payable amount for a booking.
     * Priority: approved_amount > (agreement_value - discount_amount)
     */
    public function getFinalAmount($booking)
    {
        if (!empty($booking['approved_amount'])) {
            return (float) $booking['approved_amount'];
        }
        $discount = (float) ($booking['discount_amount'] ?? 0);
        return (float) $booking['agreement_value'] - $discount;
    }

    /**
     * Directly update payment_status on lead_bookings.
     */
    public function updatePaymentStatus($bookingId, $status)
    {
        $allowed = ['unpaid', 'partial', 'paid'];
        if (!in_array($status, $allowed)) return false;

        $stmt = $this->db->prepare("
            UPDATE lead_bookings
            SET payment_status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param('si', $status, $bookingId);
        return $stmt->execute();
    }
    // Update follow-up by ID
    public function update($data, $id)
    {
        $stmt = $this->db->prepare("
        UPDATE lead_bookings SET
            lead_id = ?,
            flat_id = ?,
            project_id = ?,
            created_by = ?,
            agreement_value = ?,
            booking_date = ?,
            booking_status = ?
        WHERE id = ?
    ");

        if (!$stmt) {
            setError($this->db->error);
            return false;
        }

        $stmt->bind_param(
            "iiiidssi",
            $data['lead_id'],
            $data['flat_id'],
            $data['project_id'],
            $data['created_by'],
            $data['agreement_value'],
            $data['booking_date'],
            $data['booking_status'],
            $id
        );

        return $stmt->execute();
    }

    /**
     * Update lead_payments.amount for the booking_money payment type
     */
    public function updateBookingPaymentAmount($bookingId, $amount)
    {
        $stmt = $this->db->prepare("
        UPDATE lead_payments
        SET amount = ?
        WHERE booking_id = ? AND payment_type = 'booking_money'
    ");

        if (!$stmt) {
            setError($this->db->error);
            return false;
        }

        $stmt->bind_param("di", $amount, $bookingId);
        return $stmt->execute();
    }

    public function syncFlatStatus($flatId)
    {
        if (!$flatId) return false;
        
        $flatId = (int)$flatId;
        
        $checkStmt = $this->db->prepare("
            SELECT id FROM lead_bookings
            WHERE flat_id = ?
              AND booking_status IN ('booked', 'completed')
              AND deleted_at IS NULL
              AND id NOT IN (
                  SELECT booking_id FROM client_refund_requests 
                  WHERE booking_id IS NOT NULL AND status IN ('pending', 'approved', 'refunded')
              )
            LIMIT 1
        ");
        if (!$checkStmt) return false;
        
        $checkStmt->bind_param("i", $flatId);
        $checkStmt->execute();
        $isBooked = $checkStmt->get_result()->fetch_assoc() ? 1 : 0;
        $checkStmt->close();
        
        $statusStr = $isBooked ? 'booked' : 'available';
        
        $updStmt = $this->db->prepare("UPDATE flats SET is_booked = ?, status = ? WHERE id = ?");
        $updStmt->bind_param("isi", $isBooked, $statusStr, $flatId);
        return $updStmt->execute();
    }

    // Optional: get onboard records by lead
    public function getByLead($lead_id)
    {
        $stmt = $this->db->prepare("
        SELECT o.*, 
               COALESCE(o.past_approval_attachment, (SELECT ar.attachment FROM approval_requests ar WHERE ar.source_table = 'lead_bookings' AND ar.source_id = o.id AND ar.attachment IS NOT NULL AND ar.attachment != '' ORDER BY ar.id DESC LIMIT 1)) AS past_approval_attachment,
               p.name AS project_name, 
               f.floor_no,
               f.flat_name, 
               f.sqft,
               f.price_value, 
               us.full_name as created_user,
               u_sales.full_name as sales_user,
               COALESCE(u_sales.full_name, o.sales_agent_old) AS sales_agent_display,
               (SELECT COUNT(*) FROM lead_payments lp WHERE lp.booking_id = o.id AND lp.verification_status = 'verified') AS verified_payment_count,
               (SELECT COUNT(*) FROM lead_payments lp WHERE lp.booking_id = o.id) AS total_payment_count,
               (SELECT COALESCE(SUM(lp.amount), 0) FROM lead_payments lp WHERE lp.booking_id = o.id AND lp.verification_status = 'verified') AS total_verified_paid
        FROM lead_bookings o
        LEFT JOIN projects p ON o.project_id = p.id
        LEFT JOIN flats f ON o.flat_id = f.id
        LEFT JOIN users us ON o.created_by = us.id
        LEFT JOIN users u_sales ON o.sales_by_id = u_sales.id
        WHERE o.deleted_at IS NULL
          AND o.lead_id = ?
        ORDER BY o.id DESC
    ");

        if (!$stmt) {
            setError($this->db->error);
            return [];
        }

        $stmt->bind_param("i", $lead_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getBookingCountByLead()
    {
        $sql = "SELECT lead_id, COUNT(id) AS booking_count
                FROM lead_bookings
                WHERE deleted_at IS NULL 
                  AND booking_status NOT IN ('refunded', 'cancelled')
                GROUP BY lead_id";
        
        $result = $this->db->query($sql);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[$row['lead_id']] = (int)$row['booking_count'];
            }
        }
        return $data;
    }

    //  Optimized: শুধু current page এর lead IDs এর জন্য count করে
    public function getBookingCountByLeadIds(array $leadIds)
    {
        if (empty($leadIds)) return [];

        $ids = implode(',', array_map('intval', $leadIds));
        
        $sql = "SELECT lead_id, COUNT(id) AS booking_count
                FROM lead_bookings 
                WHERE deleted_at IS NULL 
                  AND booking_status NOT IN ('refunded', 'cancelled')
                  AND lead_id IN ($ids)
                GROUP BY lead_id";
                
        $result = $this->db->query($sql);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[$row['lead_id']] = (int)$row['booking_count'];
            }
        }
        
        return $data;
    }

    public function getBookingSellersByLeadIds(array $leadIds): array
    {
        if (empty($leadIds)) return [];

        $ids = implode(',', array_map('intval', $leadIds));
        
        $sql = "SELECT 
                    lb.lead_id, 
                    lb.sales_by_id, 
                    COALESCE(u.full_name, 'No User') AS seller_name, 
                    COUNT(lb.id) AS booking_count
                FROM lead_bookings lb
                LEFT JOIN users u ON lb.sales_by_id = u.id
                WHERE lb.deleted_at IS NULL 
                  AND lb.booking_status IN ('completed', 'booked')
                  AND lb.id NOT IN (SELECT booking_id FROM client_refund_requests WHERE status IN ('pending', 'approved', 'refunded'))
                  AND lb.lead_id IN ($ids)
                GROUP BY lb.lead_id, lb.sales_by_id, u.full_name";
                
        $result = $this->db->query($sql);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[$row['lead_id']][] = $row;
            }
        }
        return $data;
    }

    public function hasVerifiedPayment($bookingId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM lead_payments WHERE booking_id = ? AND verification_status = 'verified'");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return ($row && (int)$row['cnt'] > 0);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT lb.*, 
               COALESCE(lb.past_approval_attachment, (SELECT ar.attachment FROM approval_requests ar WHERE ar.source_table = 'lead_bookings' AND ar.source_id = lb.id AND ar.attachment IS NOT NULL AND ar.attachment != '' ORDER BY ar.id DESC LIMIT 1)) AS past_approval_attachment,
               l.full_name, 
               l.assigned_id,
               COALESCE(NULLIF(TRIM(l.phone), ''), TRIM(l.phone_number)) AS phone,
               l.phone_number,
               p.name AS project_name,
               p.total_share,
               f.floor_no, 
               f.price_value,
               f.land_share_value,
               f.booking_money,
               f.registration_money,
               f.sqft,
               f.flat_name AS unit_name,
               (SELECT COUNT(*) FROM lead_payments lp WHERE lp.booking_id = lb.id AND lp.verification_status = 'verified') AS verified_payment_count,
               (SELECT COUNT(*) FROM lead_payments lp WHERE lp.booking_id = lb.id) AS total_payment_count
        FROM lead_bookings lb
        LEFT JOIN leads l ON lb.lead_id = l.id
        LEFT JOIN flats f ON lb.flat_id = f.id
        LEFT JOIN projects p ON lb.project_id = p.id
        WHERE lb.id = ?
        LIMIT 1
    ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc(); // single record
    }

    public function updateBooking($id, $data)
    {
        $isManagementOrSalesHead = in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 3, 9, 20, 33]);
        if (!$isManagementOrSalesHead && $this->hasVerifiedPayment($id)) {
            return ['success' => false, 'message' => 'Cannot edit booking because payments have already been verified by Accounts.'];
        }

        $existing = $this->db->query("SELECT * FROM lead_bookings WHERE id = {$id}")->fetch_assoc();
        if (!$existing) {
            return ['success' => false, 'message' => 'Booking not found.'];
        }

        $this->db->begin_transaction();
        try {
            $oldFlatId = !empty($existing['flat_id']) ? (int)$existing['flat_id'] : null;
            $newFlatId = !empty($data['flat_id']) ? (int)$data['flat_id'] : null;

            // If flat changed, release old flat and reserve new flat
            if ($oldFlatId && $oldFlatId !== $newFlatId) {
                // Update old flat conditionally
                $this->syncFlatStatus($oldFlatId);
            }

            if ($newFlatId && $oldFlatId !== $newFlatId) {
                $stmtNew = $this->db->prepare("UPDATE flats SET status = 'booked', is_booked = 1 WHERE id = ?");
                $stmtNew->bind_param("i", $newFlatId);
                $stmtNew->execute();
            }

            // If discount was already approved by management, preserve the approved values
            $isManagementApproved = in_array($existing['approval_status'] ?? '', ['approved', 'partial']);
            if ($isManagementApproved) {
                $discount = (float)($existing['discount_amount'] ?? 0);
                $approvedAmt = $existing['approved_amount'] ?? null;
                $approvalStatus = $existing['approval_status'];
            } else {
                $discount = (float)($data['discount_amount'] ?? 0);
                $approvalStatus = ($discount > 0) ? 'pending' : 'not_required';
                $approvedAmt = null;

                // Sync approval_requests
                $pastApprovalAttachment = $data['past_approval_attachment'] ?? $existing['past_approval_attachment'];
                if ($discount > 0) {
                    $salesPersonId = !empty($data['sales_by_id']) ? (int)$data['sales_by_id'] : (!empty($existing['sales_by_id']) ? (int)$existing['sales_by_id'] : 0);
                    if ($salesPersonId <= 0 && !empty($existing['lead_id'])) {
                        $lRow = $this->db->query("SELECT assigned_id FROM leads WHERE id = " . (int)$existing['lead_id'] . " LIMIT 1")->fetch_assoc();
                        $salesPersonId = (int)($lRow['assigned_id'] ?? 0);
                    }
                    $requestedBy = ($salesPersonId > 0) ? $salesPersonId : (int)($_SESSION['user']['id'] ?? 1);

                    $checkApp = $this->db->query("SELECT id FROM approval_requests WHERE source_table = 'lead_bookings' AND source_id = {$id} LIMIT 1")->fetch_assoc();
                    if ($checkApp) {
                        $appUpSql = "UPDATE approval_requests SET request_amount = {$discount}, requested_by = {$requestedBy}, status = 'pending', approved_amount = NULL, updated_at = NOW()";
                        if (!empty($pastApprovalAttachment)) {
                            $appUpSql .= ", attachment = '" . $this->db->real_escape_string($pastApprovalAttachment) . "'";
                        }
                        $appUpSql .= " WHERE id = " . (int)$checkApp['id'];
                        $this->db->query($appUpSql);
                    } else {
                        $stmtIns = $this->db->prepare("INSERT INTO approval_requests (department, request_type, title, source_table, source_id, request_amount, status, requested_by, approve_role, attachment, created_at) VALUES ('Sales', 'discount', 'Lead Booking Discount Approval', 'lead_bookings', ?, ?, 'pending', ?, 'Management', ?, NOW())");
                        if ($stmtIns) {
                            $stmtIns->bind_param("idis", $id, $discount, $requestedBy, $pastApprovalAttachment);
                            $stmtIns->execute();
                            $stmtIns->close();
                        }
                    }
                } else {
                    $this->db->query("DELETE FROM approval_requests WHERE source_table = 'lead_bookings' AND source_id = {$id}");
                }
            }

            $pastApprovalAttachment = $data['past_approval_attachment'] ?? $existing['past_approval_attachment'];

            $stmt = $this->db->prepare("
                UPDATE lead_bookings SET
                    project_id = ?,
                    flat_id = ?,
                    agreement_value = ?,
                    booking_date = ?,
                    discount_amount = ?,
                    approved_amount = ?,
                    approval_status = ?,
                    past_approval_attachment = ?,
                    sales_by_id = ?
                WHERE id = ?
            ");

            $projectId = (int)($data['project_id'] ?? $existing['project_id']);
            $agreementVal = (float)($data['agreement_value'] ?? $existing['agreement_value']);
            $bookingDate = $data['booking_date'] ?? $existing['booking_date'];
            $salesById = isset($data['sales_by_id']) ? (int)$data['sales_by_id'] : $existing['sales_by_id'];

            $stmt->bind_param(
                "iidsddssii",
                $projectId,
                $newFlatId,
                $agreementVal,
                $bookingDate,
                $discount,
                $approvedAmt,
                $approvalStatus,
                $pastApprovalAttachment,
                $salesById,
                $id
            );


            if (!$stmt->execute()) {
                throw new Exception("Booking update failed: " . $stmt->error);
            }

            // Update leads assigned_id if sales_by_id was changed
            if (isset($data['sales_by_id']) && $data['sales_by_id'] != $existing['sales_by_id']) {
                $leadId = $existing['lead_id'] ?? 0;
                if ($leadId > 0) {
                    $stmtLead = $this->db->prepare("UPDATE leads SET assigned_id = ? WHERE id = ?");
                    if ($stmtLead) {
                        $stmtLead->bind_param("ii", $salesById, $leadId);
                        $stmtLead->execute();
                    }
                }
            }

            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Delete booking and cascade delete unverified payments & release flat
    public function deleteCascade($bookingId)
    {
        if ($this->hasVerifiedPayment($bookingId)) {
            return ['success' => false, 'message' => 'Cannot delete booking because payments have already been verified by Accounts.'];
        }

        $existing = $this->db->query("SELECT * FROM lead_bookings WHERE id = {$bookingId}")->fetch_assoc();
        if (!$existing) {
            return ['success' => false, 'message' => 'Booking not found.'];
        }

        $this->db->begin_transaction();
        try {
            $flatId = !empty($existing['flat_id']) ? (int)$existing['flat_id'] : null;
            $leadId = (int)($existing['lead_id'] ?? 0);

            // 1. Delete related lead_payments
            $stmtP = $this->db->prepare("DELETE FROM lead_payments WHERE booking_id = ?");
            $stmtP->bind_param("i", $bookingId);
            $stmtP->execute();

            // 2. Delete related approval_requests
            $stmtA = $this->db->prepare("DELETE FROM approval_requests WHERE source_table = 'lead_bookings' AND source_id = ?");
            $stmtA->bind_param("i", $bookingId);
            $stmtA->execute();

            // 3. Delete lead_bookings record
            $stmtB = $this->db->prepare("DELETE FROM lead_bookings WHERE id = ?");
            $stmtB->bind_param("i", $bookingId);
            $stmtB->execute();

            // 4. Release flat conditionally in flats table
            if ($flatId) {
                // Execute deletion commit first before syncing flat status
            }

            $this->db->commit();

            // Sync flat status after transaction is committed
            if (isset($flatId) && $flatId) {
                $this->syncFlatStatus($flatId);
            }

            return ['success' => true, 'lead_id' => $leadId];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function delete($id)
    {
        return $this->deleteCascade($id);
    }


    public function getSalesReport($type = 'daily', $search = '', $projectId = '', $userId = '')
    {
        if ($type === 'daily') {
            $query = "SELECT 
                lb.*, 
                l.full_name AS client_name, 
                l.phone, 
                p.name AS project_name, 
                l.id as lead_id,
                f.flat_name, 
                f.sqft,
                (f.price_value - IFNULL(lb.discount_amount,0)) AS final_price,
                u.full_name AS user_name, 
                u.id AS sales_user_id,
                us.full_name AS assigned_user_name, 
                us.id AS user_id,
                COALESCE(u.full_name, lb.sales_agent_old) AS sales_agent_display
          FROM lead_bookings lb
          LEFT JOIN leads l ON lb.lead_id = l.id
          LEFT JOIN projects p ON lb.project_id = p.id
          LEFT JOIN flats f ON lb.flat_id = f.id
          LEFT JOIN users u ON lb.sales_by_id = u.id
          LEFT JOIN users us ON l.assigned_id = us.id
          WHERE lb.deleted_at IS NULL";

            $types = '';
            $params = [];

            if (!empty($userId)) {
                $query .= " AND lb.sales_by_id = ?";
                $types .= 'i';
                $params[] = $userId;
            }

            if (!empty($projectId)) {
                $query .= " AND lb.project_id = ?";
                $types .= 'i';
                $params[] = $projectId;
            }

            if (!empty($search)) {
                $query .= " AND (l.full_name LIKE ? OR f.flat_name LIKE ? OR p.name LIKE ?)";
                $types .= 'sss';
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            $query .= " ORDER BY lb.booking_date ASC";

            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                setError($this->db->error);
                return [];
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        }


        // ------------------- Monthly -------------------
        elseif ($type === 'monthly') {

            $query = "SELECT 
        DATE_FORMAT(lb.booking_date, '%Y-%m') AS month_key,
        DATE_FORMAT(lb.booking_date, '%M %Y') AS month_name,
        COUNT(lb.id) AS total_flats,
        COUNT(DISTINCT lb.lead_id) AS total_clients,
        SUM(lb.agreement_value) AS total_booking_value,
        SUM(f.price_value - IFNULL(lb.discount_amount,0)) AS share_price,
        SUM(CASE WHEN lb.booking_status = 'completed' THEN 1 ELSE 0 END) AS completed
    FROM lead_bookings lb
    LEFT JOIN leads l ON lb.lead_id = l.id
    LEFT JOIN projects p ON lb.project_id = p.id
    LEFT JOIN flats f ON lb.flat_id = f.id
    WHERE lb.deleted_at IS NULL";

            $types = '';
            $params = [];

            // ✅ user filter
            if (!empty($userId)) {
                $query .= " AND lb.sales_by_id = ?";
                $types .= 'i';
                $params[] = $userId;
            }

            // ✅ project filter
            if (!empty($projectId)) {
                $query .= " AND lb.project_id = ?";
                $types .= 'i';
                $params[] = $projectId;
            }

            // ✅ search filter
            if (!empty($search)) {
                $query .= " AND (l.full_name LIKE ? OR f.flat_name LIKE ? OR p.name LIKE ?)";
                $types .= 'sss';
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            // ✅ GROUP BY fix (important)
            $query .= " 
GROUP BY 
    DATE_FORMAT(lb.booking_date, '%Y-%m'), 
    DATE_FORMAT(lb.booking_date, '%M %Y')
    ";

            $stmt = $this->db->prepare($query);

            if (!$stmt) {
                setError($this->db->error);
                return [];
            }

            // ✅ bind যদি param থাকে
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            return $result->fetch_all(MYSQLI_ASSOC);
        }


        // ------------------- Yearly -------------------
        elseif ($type === 'yearly') {
            $query = "SELECT 
            YEAR(lb.booking_date) AS year,
            COUNT(lb.id) AS total_flats,
            COUNT(DISTINCT lb.lead_id) AS total_clients,
            SUM(lb.agreement_value - IFNULL(lb.discount_amount,0)) AS total_amount,
            SUM(CASE WHEN lb.booking_status = 'completed' THEN 1 ELSE 0 END) AS completed
        FROM lead_bookings lb
        LEFT JOIN leads l ON lb.lead_id = l.id
        LEFT JOIN projects p ON lb.project_id = p.id
        LEFT JOIN flats f ON lb.flat_id = f.id
        WHERE lb.deleted_at IS NULL";

            if (!empty($userId)) {
                $query .= " AND lb.sales_by_id = $userId";
            }

            if (!empty($projectId)) {
                $query .= " AND lb.project_id = $projectId";
            }

            if (!empty($search)) {
                $query .= " AND (l.full_name LIKE '%$search%' OR f.flat_name LIKE '%$search%' OR p.name LIKE '%$search%')";
            }

            $query .= " GROUP BY year ORDER BY year DESC";

            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                setError($this->db->error);
                return [];
            }

            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        }
    }


    public function getMyTotalFlatSales(int $userId): array
    {
        $userId = (int) $userId;

        $sql = "
        SELECT 
            COUNT(*) AS total_flats
        FROM lead_bookings
        WHERE sales_by_id = {$userId}
          AND booking_status IN ('completed', 'booked')
          AND deleted_at IS NULL
    ";

        $row = $this->db->query($sql)->fetch_assoc();

        return [
            'total_flats' => (int) ($row['total_flats'] ?? 0),
        ];
    }
}
