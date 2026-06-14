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




    public function getAllOnboardClientBooking($year = null, $month = null)
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
             AND lb.booking_status IN ('booked', 'completed')
             ";

        $params = [];
        $types  = "";

        if (!empty($year)) {
            $sql .= " AND YEAR(lb.booking_date) = ?";
            $params[] = (int)$year;
            $types   .= "i";
        }

        if (!empty($month)) {
            $sql .= " AND MONTH(lb.booking_date) = ?";
            $params[] = (int)$month;
            $types   .= "i";
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

    public function getAllOnboardClient($year = null, $month = null)
    {
        $sql = "SELECT 
                l.id AS lead_id, 
                l.full_name, 
                l.email, 
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

        $sql .= " GROUP BY l.id, l.full_name, l.email
              ORDER BY l.full_name ASC";

        $stmt = $this->db->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }



    public function getAllMyOnboardProject($userId, $year = null, $month = null)
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
            GROUP BY booking_id
        ) AS lp_sum ON lp_sum.booking_id = lb.id
        WHERE lb.deleted_at IS NULL
          AND lb.booking_status IN ('booked', 'completed')
          AND lb.sales_by_id = ?";

        $params = [$userId];
        $types  = "i";

        if (!empty($year)) {
            $sql .= " AND YEAR(lb.booking_date) = ?";
            $params[] = (int)$year;
            $types   .= "i";
        }

        if (!empty($month)) {
            $sql .= " AND MONTH(lb.booking_date) = ?";
            $params[] = (int)$month;
            $types   .= "i";
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
    public function getTotalBookings($period = null)
    {
        $sqlQuery = "SELECT COUNT(*) AS onboarded 
                 FROM lead_bookings 
                 WHERE booking_status = 'booked' 
                 AND deleted_at IS NULL";

        if ($period) {
            switch ($period) {
                case 'daily':
                    $sqlQuery .= " AND DATE(created_at) = CURDATE()";
                    break;
                case 'weekly':
                    $sqlQuery .= " AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    break;
                case 'monthly':
                    $sqlQuery .= " AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                    break;
                case 'yearly':
                    $sqlQuery .= " AND created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                    break;
            }
        }

        $result = $this->db->query($sqlQuery);
        $row = $result->fetch_assoc();
        return (int)($row['onboarded'] ?? 0);
    }

    public function createFromCsvBulk($data)
    {
        $sql = "INSERT INTO lead_bookings (
            lead_id, project_id, flat_id, agreement_value, discount_amount, 
            booking_date, booking_status, created_by, sales_by_id, sales_agent_old, 
            approval_status, approved_amount, service_fee_balance, landshare_status, 
            payment_status, attachment_image, uploaded_from
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, 
            ?, ?, ?
        )";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new Exception($this->db->error);

        $v = function($key) use ($data) {
            return (isset($data[$key]) && trim($data[$key]) !== '') ? trim($data[$key]) : null;
        };

        $v_lead_id = $v('lead_id'); $v_project_id = $v('project_id'); $v_flat_id = $v('flat_id'); $v_agreement_value = $v('agreement_value'); $v_discount_amount = $v('discount_amount');
        $v_booking_date = $v('booking_date'); $v_booking_status = $v('booking_status'); $v_created_by = $v('created_by'); $v_sales_by_id = $v('sales_by_id'); $v_sales_agent_old = $v('sales_agent_old');
        $v_approval_status = $v('approval_status'); $v_approved_amount = $v('approved_amount'); $v_service_fee_balance = $v('service_fee_balance'); $v_landshare_status = $v('landshare_status');
        $v_payment_status = $v('payment_status'); $v_attachment_image = $v('attachment_image');
        $v_uploaded_from = $data['uploaded_from'] ?? 'From Company csv';

        $bindStr = str_repeat('s', 17);
        $stmt->bind_param(
            $bindStr,
            $v_lead_id, $v_project_id, $v_flat_id, $v_agreement_value, $v_discount_amount,
            $v_booking_date, $v_booking_status, $v_created_by, $v_sales_by_id, $v_sales_agent_old,
            $v_approval_status, $v_approved_amount, $v_service_fee_balance, $v_landshare_status,
            $v_payment_status, $v_attachment_image, $v_uploaded_from
        );

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        return $this->db->insert_id;
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
            sales_by_id,
            sales_agent_old
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception($this->db->error);
        }

        // 4 Bind params (note: approval_status is string 's', sales_agent_old is 's')
        $stmt->bind_param(
            "iiiidsssssi",
            $data['lead_id'],         // i
            $data['project_id'],      // i
            $data['flat_id'],         // i
            $data['created_by'],      // i
            $data['agreement_value'], // d
            $data['booking_date'],    // s
            $data['booking_status'],  // s
            $discountAmount,          // s (nullable)
            $approvalStatus,          // s
            $data['sales_by_id'],     // i
            $salesAgentOld            // s (nullable varchar)
        );

        // 5 Execute
        if ($stmt->execute()) {
            return $this->db->insert_id;
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

    // Optional: get onboard records by lead
    public function getByLead($lead_id)
    {
        $stmt = $this->db->prepare("
        SELECT o.*, 
               p.name AS project_name, 
               f.floor_no,
               f.flat_name, 
               f.sqft,
               f.price_value, 
               us.full_name as created_user,
               u_sales.full_name as sales_user,
               COALESCE(u_sales.full_name, o.sales_agent_old) AS sales_agent_display
        FROM lead_bookings o
        LEFT JOIN projects p ON o.project_id = p.id
        LEFT JOIN flats f ON o.flat_id = f.id
        LEFT JOIN users us ON o.created_by = us.id
        LEFT JOIN users u_sales ON o.sales_by_id = u_sales.id
        WHERE o.deleted_at IS NULL
          AND o.lead_id = ?
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
        $stmt = $this->db->prepare(
            "SELECT 
            lb.lead_id,
            COUNT(DISTINCT lb.id) AS booking_count
         FROM lead_bookings lb
         INNER JOIN lead_payments lp 
            ON lp.booking_id = lb.id
         WHERE lb.deleted_at IS NULL AND lb.booking_status NOT IN ('refunded', 'cancelled')
         GROUP BY lb.lead_id"
        );

        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['lead_id']] = $row['booking_count'];
        }

        return $data;
    }

    // ✅ Optimized: শুধু current page এর lead IDs এর জন্য count করে
    public function getBookingCountByLeadIds(array $leadIds)
    {
        if (empty($leadIds)) return [];

        $ids = implode(',', array_map('intval', $leadIds));
        $sql = "SELECT lb.lead_id, COUNT(DISTINCT lb.id) AS booking_count
                FROM lead_bookings lb
                INNER JOIN lead_payments lp ON lp.booking_id = lb.id
                WHERE lb.lead_id IN ($ids) 
                  AND lb.deleted_at IS NULL 
                  AND lb.booking_status NOT IN ('refunded', 'cancelled')
                GROUP BY lb.lead_id";

        $result = $this->db->query($sql);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[$row['lead_id']] = $row['booking_count'];
            }
        }
        return $data;
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT lb.*, 
               l.full_name, 
               p.name AS project_name,
               p.total_share,
               f.floor_no, 
               f.price_value,
               f.land_share_value,
               f.sqft,
               f.flat_name AS unit_name
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


    public function getAllBooking()
    {
        $stmt = $this->db->prepare("SELECT lb.*, l.full_name,l.lead_image,l.phone, p.name AS project_name,f.price_value,f.flat_name,f.sqft,f.is_booked
            FROM lead_bookings lb LEFT JOIN leads l ON lb.lead_id = l.id 
            LEFT JOIN projects p ON lb.project_id = p.id
            LEFT JOIN flats f ON lb.flat_id = f.id
            Where lb.deleted_at IS NULL");

        if (!$stmt) {
            setError($this->db->error);
            return false;
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Delete follow-up by ID
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM lead_follow_ups WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
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
