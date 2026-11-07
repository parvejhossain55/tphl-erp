<?php
/**
 * ClientRefund Model
 * Table: client_refund_requests
 *
 * Handles client flat refund lifecycle:
 * create → approve (cancel booking + release flat + cancel EMIs + create cashout) → refunded
 */
class ClientRefund extends Model
{
    protected $table = 'client_refund_requests';

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    // =========================================================================
    // READ
    // =========================================================================

    /** Get all refund requests with related info */
    public function getAll($filterUserId = null)
    {
        $sql = "SELECT crr.*,
                       l.full_name      AS client_name,
                       l.phone          AS client_phone,
                       p.name           AS project_name,
                       f.flat_name,
                       f.floor_no,
                       u_req.full_name  AS requested_by_name,
                       r_req.name       AS requested_by_role,
                       u_app.full_name  AS approved_by_name,
                       r_app.name       AS approved_by_role
                FROM {$this->table} crr
                LEFT JOIN leads    l     ON l.id  = crr.lead_id
                LEFT JOIN projects p     ON p.id  = crr.project_id
                LEFT JOIN flats    f     ON f.id  = crr.flat_id
                LEFT JOIN users    u_req ON u_req.id = crr.requested_by
                LEFT JOIN roles    r_req ON r_req.id = u_req.role_id
                LEFT JOIN users    u_app ON u_app.id = crr.approved_by
                LEFT JOIN roles    r_app ON r_app.id = u_app.role_id";

        if ($filterUserId) {
            $sql .= " WHERE crr.requested_by = " . (int)$filterUserId;
        }

        $sql .= " ORDER BY crr.created_at DESC";

        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get KPI summary statistics and project-wise breakdown
     */
    public function getSummaryStats($filterUserId = null, $filters = [])
    {
        $where = [];
        if ($filterUserId) {
            $where[] = "crr.requested_by = " . (int)$filterUserId;
        }

        if (!empty($filters['project_id']) && $filters['project_id'] !== 'all') {
            $where[] = "crr.project_id = " . (int)$filters['project_id'];
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $statusEsc = $this->db->real_escape_string(trim($filters['status']));
            $where[] = "crr.status = '{$statusEsc}'";
        }

        if (!empty($filters['date_from'])) {
            $fromEsc = $this->db->real_escape_string(trim($filters['date_from']));
            $where[] = "DATE(crr.created_at) >= '{$fromEsc}'";
        }

        if (!empty($filters['date_to'])) {
            $toEsc = $this->db->real_escape_string(trim($filters['date_to']));
            $where[] = "DATE(crr.created_at) <= '{$toEsc}'";
        }

        if (!empty($filters['search'])) {
            $q = $this->db->real_escape_string(trim($filters['search']));
            $where[] = "(l.full_name LIKE '%{$q}%' 
                         OR l.phone LIKE '%{$q}%' 
                         OR f.flat_name LIKE '%{$q}%' 
                         OR p.name LIKE '%{$q}%' 
                         OR crr.reason LIKE '%{$q}%' 
                         OR crr.id = '{$q}')";
        }

        $whereClause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

        // 1. Overall / Filtered stats
        $statsSql = "SELECT 
                        COUNT(crr.id) AS total_requests,
                        SUM(CASE WHEN crr.status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                        SUM(CASE WHEN crr.status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
                        SUM(CASE WHEN crr.status = 'refunded' THEN 1 ELSE 0 END) AS refunded_count,
                        SUM(CASE WHEN crr.status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
                        COALESCE(SUM(crr.total_paid), 0) AS total_verified_paid,
                        COALESCE(SUM(crr.retention_amount), 0) AS total_retention,
                        COALESCE(SUM(crr.refund_amount), 0) AS total_refund_amount,
                        COALESCE(SUM(CASE WHEN crr.status = 'refunded' THEN crr.refund_amount ELSE 0 END), 0) AS total_refunded_paid,
                        COALESCE(SUM(CASE WHEN crr.status = 'refunded' THEN crr.retention_amount ELSE 0 END), 0) AS refunded_retention,
                        COALESCE(SUM(CASE WHEN crr.status IN ('pending', 'approved') THEN crr.retention_amount ELSE 0 END), 0) AS pending_retention,
                        COALESCE(SUM(CASE WHEN crr.status IN ('pending', 'approved') THEN crr.refund_amount ELSE 0 END), 0) AS pending_refund_amount,
                        COALESCE(SUM(CASE WHEN crr.status = 'refunded' THEN crr.total_paid ELSE 0 END), 0) AS refunded_total_paid
                     FROM {$this->table} crr
                     LEFT JOIN leads    l ON l.id = crr.lead_id
                     LEFT JOIN projects p ON p.id = crr.project_id
                     LEFT JOIN flats    f ON f.id = crr.flat_id
                     {$whereClause}";
        
        $statsRes = $this->db->query($statsSql);
        $stats = $statsRes ? $statsRes->fetch_assoc() : [
            'total_requests'        => 0,
            'pending_count'         => 0,
            'approved_count'        => 0,
            'refunded_count'        => 0,
            'rejected_count'        => 0,
            'total_verified_paid'   => 0,
            'total_retention'       => 0,
            'total_refund_amount'   => 0,
            'total_refunded_paid'   => 0,
            'refunded_retention'    => 0,
            'pending_retention'     => 0,
            'pending_refund_amount' => 0,
            'refunded_total_paid'   => 0,
        ];

        // Ensure float formatting
        $stats['total_requests']        = (int)($stats['total_requests'] ?? 0);
        $stats['pending_count']         = (int)($stats['pending_count'] ?? 0);
        $stats['approved_count']        = (int)($stats['approved_count'] ?? 0);
        $stats['refunded_count']        = (int)($stats['refunded_count'] ?? 0);
        $stats['rejected_count']        = (int)($stats['rejected_count'] ?? 0);
        $stats['total_verified_paid']   = (float)($stats['total_verified_paid'] ?? 0);
        $stats['total_retention']       = (float)($stats['total_retention'] ?? 0);
        $stats['total_refund_amount']   = (float)($stats['total_refund_amount'] ?? 0);
        $stats['total_refunded_paid']   = (float)($stats['total_refunded_paid'] ?? 0);
        $stats['refunded_retention']    = (float)($stats['refunded_retention'] ?? 0);
        $stats['pending_retention']     = (float)($stats['pending_retention'] ?? 0);
        $stats['pending_refund_amount'] = (float)($stats['pending_refund_amount'] ?? 0);
        $stats['refunded_total_paid']   = (float)($stats['refunded_total_paid'] ?? 0);

        // 2. Project breakdown: calculate across user scope (ignoring project filter so pills show all projects)
        $projWhereSql = $filterUserId ? " WHERE crr.requested_by = " . (int)$filterUserId : "";
        $projSql = "SELECT 
                        crr.project_id,
                        COALESCE(p.name, 'Unassigned Project') AS project_name,
                        COUNT(crr.id) AS total_refunds,
                        SUM(CASE WHEN crr.status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                        SUM(CASE WHEN crr.status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
                        SUM(CASE WHEN crr.status = 'refunded' THEN 1 ELSE 0 END) AS refunded_count,
                        SUM(CASE WHEN crr.status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
                        COALESCE(SUM(crr.total_paid), 0) AS total_paid,
                        COALESCE(SUM(crr.retention_amount), 0) AS total_retention,
                        COALESCE(SUM(crr.refund_amount), 0) AS total_refund,
                        COALESCE(SUM(CASE WHEN crr.status = 'refunded' THEN crr.retention_amount ELSE 0 END), 0) AS refunded_retention,
                        COALESCE(SUM(CASE WHEN crr.status = 'refunded' THEN crr.refund_amount ELSE 0 END), 0) AS refunded_paid
                    FROM {$this->table} crr
                    LEFT JOIN projects p ON p.id = crr.project_id
                    {$projWhereSql}
                    GROUP BY crr.project_id, p.name
                    ORDER BY total_refunds DESC, p.name ASC";

        $projRes = $this->db->query($projSql);
        $projectBreakdown = $projRes ? $projRes->fetch_all(MYSQLI_ASSOC) : [];

        $stats['project_breakdown'] = $projectBreakdown;
        return $stats;
    }

    /**
     * Get filtered & paginated refund requests
     */
    public function getFilteredRefunds($filterUserId = null, $filters = [], $limit = 20, $offset = 0)
    {
        $where = [];

        if ($filterUserId) {
            $where[] = "crr.requested_by = " . (int)$filterUserId;
        }

        if (!empty($filters['project_id']) && $filters['project_id'] !== 'all') {
            $where[] = "crr.project_id = " . (int)$filters['project_id'];
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $statusEsc = $this->db->real_escape_string(trim($filters['status']));
            $where[] = "crr.status = '{$statusEsc}'";
        }

        if (!empty($filters['date_from'])) {
            $fromEsc = $this->db->real_escape_string(trim($filters['date_from']));
            $where[] = "DATE(crr.created_at) >= '{$fromEsc}'";
        }

        if (!empty($filters['date_to'])) {
            $toEsc = $this->db->real_escape_string(trim($filters['date_to']));
            $where[] = "DATE(crr.created_at) <= '{$toEsc}'";
        }

        if (!empty($filters['search'])) {
            $q = $this->db->real_escape_string(trim($filters['search']));
            $where[] = "(l.full_name LIKE '%{$q}%' 
                         OR l.phone LIKE '%{$q}%' 
                         OR f.flat_name LIKE '%{$q}%' 
                         OR p.name LIKE '%{$q}%' 
                         OR crr.reason LIKE '%{$q}%' 
                         OR crr.id = '{$q}')";
        }

        $whereClause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

        // Count query
        $countSql = "SELECT COUNT(crr.id) AS total_count
                     FROM {$this->table} crr
                     LEFT JOIN leads    l ON l.id = crr.lead_id
                     LEFT JOIN projects p ON p.id = crr.project_id
                     LEFT JOIN flats    f ON f.id = crr.flat_id
                     {$whereClause}";
        $countRes = $this->db->query($countSql);
        $total = $countRes ? (int)($countRes->fetch_assoc()['total_count'] ?? 0) : 0;

        // Data query
        $dataSql = "SELECT crr.*,
                           l.full_name      AS client_name,
                           l.phone          AS client_phone,
                           p.name           AS project_name,
                           f.flat_name,
                           f.floor_no,
                           u_req.full_name  AS requested_by_name,
                           r_req.name       AS requested_by_role,
                           u_app.full_name  AS approved_by_name,
                           r_app.name       AS approved_by_role
                    FROM {$this->table} crr
                    LEFT JOIN leads    l     ON l.id  = crr.lead_id
                    LEFT JOIN projects p     ON p.id  = crr.project_id
                    LEFT JOIN flats    f     ON f.id  = crr.flat_id
                    LEFT JOIN users    u_req ON u_req.id = crr.requested_by
                    LEFT JOIN roles    r_req ON r_req.id = u_req.role_id
                    LEFT JOIN users    u_app ON u_app.id = crr.approved_by
                    LEFT JOIN roles    r_app ON r_app.id = u_app.role_id
                    {$whereClause}
                    ORDER BY crr.created_at DESC
                    LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $dataRes = $this->db->query($dataSql);
        $data = $dataRes ? $dataRes->fetch_all(MYSQLI_ASSOC) : [];

        return [
            'data'  => $data,
            'total' => $total,
        ];
    }

    /** Find single refund with full relations */
    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT crr.*,
                    l.full_name      AS client_name,
                    l.phone          AS client_phone,
                    l.email          AS client_email,
                    p.name           AS project_name,
                    f.flat_name,
                    f.floor_no,
                    f.price_value    AS flat_price,
                    lb.booking_date,
                    lb.agreement_value,
                    lb.booking_status,
                    u_req.full_name  AS requested_by_name,
                    r_req.name       AS requested_by_role,
                    u_app.full_name  AS approved_by_name,
                    r_app.name       AS approved_by_role
             FROM {$this->table} crr
             LEFT JOIN leads         l     ON l.id  = crr.lead_id
             LEFT JOIN projects      p     ON p.id  = crr.project_id
             LEFT JOIN flats         f     ON f.id  = crr.flat_id
             LEFT JOIN lead_bookings lb    ON lb.id = crr.booking_id
             LEFT JOIN users         u_req ON u_req.id = crr.requested_by
             LEFT JOIN roles         r_req ON r_req.id = u_req.role_id
             LEFT JOIN users         u_app ON u_app.id = crr.approved_by
             LEFT JOIN roles         r_app ON r_app.id = u_app.role_id
             WHERE crr.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /** Check if a non-rejected refund already exists for this booking */
    public function hasActiveRefund($bookingId)
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM {$this->table}
             WHERE booking_id = ? AND status NOT IN ('rejected')
             LIMIT 1"
        );
        $stmt->bind_param('i', $bookingId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return !empty($result);
    }

    /** Get total verified payments for a booking */
    public function getTotalVerifiedPaid($bookingId)
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount), 0) AS total_paid
             FROM lead_payments
             WHERE booking_id = ? AND verification_status = 'verified'"
        );
        $stmt->bind_param('i', $bookingId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (float)($row['total_paid'] ?? 0);
    }

    /** Get all payments for a booking (for display) */
    public function getPaymentsForBooking($bookingId)
    {
        $stmt = $this->db->prepare(
            "SELECT lp.*,
                    u.full_name AS created_by_name
             FROM lead_payments lp
             LEFT JOIN users u ON u.id = lp.created_by
             WHERE lp.booking_id = ?
             ORDER BY lp.payment_date ASC, lp.id ASC"
        );
        $stmt->bind_param('i', $bookingId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Get booking details */
    public function getBookingDetails($bookingId)
    {
        $stmt = $this->db->prepare(
            "SELECT lb.*,
                    l.full_name  AS client_name,
                    l.phone      AS client_phone,
                    l.email      AS client_email,
                    p.name       AS project_name,
                    f.flat_name,
                    f.floor_no,
                    f.price_value AS flat_price
             FROM lead_bookings lb
             LEFT JOIN leads    l ON l.id = lb.lead_id
             LEFT JOIN projects p ON p.id = lb.project_id
             LEFT JOIN flats    f ON f.id = lb.flat_id
             WHERE lb.id = ?"
        );
        $stmt->bind_param('i', $bookingId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================================
    // WRITE
    // =========================================================================

    /** Create a new refund request */
    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table}
             (booking_id, lead_id, project_id, flat_id,
              total_paid, retention_amount, refund_amount,
              reason, document, requested_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            die("Database Error in creating refund: " . $this->db->error);
        }

        $stmt->bind_param(
            'iiiidddssi',
            $data['booking_id'],
            $data['lead_id'],
            $data['project_id'],
            $data['flat_id'],
            $data['total_paid'],
            $data['retention_amount'],
            $data['refund_amount'],
            $data['reason'],
            $data['document'],
            $data['requested_by']
        );

        if ($stmt->execute()) {
            return $stmt->insert_id;
        }

        error_log("ClientRefund create failed: " . $stmt->error);
        return false;
    }

    /** Approve refund + cancel booking + release flat + cancel pending EMIs */
    public function approve($id, $approverId, $approvedAmount = null)
    {
        $this->db->begin_transaction();
        try {
            $refund = $this->find($id);
            if (!$refund || $refund['status'] !== 'pending') {
                throw new \Exception("Refund not in pending status.");
            }

            // Update amounts if a different approved amount is provided
            if ($approvedAmount !== null && $approvedAmount != $refund['refund_amount']) {
                $retentionAmount = max(0, $refund['total_paid'] - $approvedAmount);
                $updateAmountStmt = $this->db->prepare(
                    "UPDATE {$this->table} SET refund_amount = ?, retention_amount = ? WHERE id = ?"
                );
                $updateAmountStmt->bind_param('ddi', $approvedAmount, $retentionAmount, $id);
                $updateAmountStmt->execute();
            }

            // 1. Update refund status to approved
            $stmt = $this->db->prepare(
                "UPDATE {$this->table}
                 SET status = 'approved', approved_by = ?, approved_at = NOW()
                 WHERE id = ? AND status = 'pending'"
            );
            $stmt->bind_param('ii', $approverId, $id);
            if (!$stmt->execute() || $stmt->affected_rows === 0) {
                throw new \Exception("Failed to update refund status.");
            }

            // 2. Cancel the booking
            $stmt2 = $this->db->prepare(
                "UPDATE lead_bookings
                 SET booking_status = 'refunded', landshare_status = 'cancelled'
                 WHERE id = ?"
            );
            $stmt2->bind_param('i', $refund['booking_id']);
            $stmt2->execute();

            // 3. Release the flat
            $stmt3 = $this->db->prepare(
                "UPDATE flats
                 SET status = 'available', is_booked = 0
                 WHERE id = ?"
            );
            $stmt3->bind_param('i', $refund['flat_id']);
            $stmt3->execute();

            // 3.5 Update lead status
            $stmtLead = $this->db->prepare(
                "UPDATE leads
                 SET last_lead_action = 'cancel'
                 WHERE id = ?"
            );
            $stmtLead->bind_param('i', $refund['lead_id']);
            $stmtLead->execute();

            // 4. Cancel all pending EMIs for this booking
            $stmt4 = $this->db->prepare(
                "UPDATE emi_schedules
                 SET status = 'cancelled'
                 WHERE lead_booking_id = ? AND status IN ('pending', 'missed')"
            );
            $stmt4->bind_param('i', $refund['booking_id']);
            $stmt4->execute();

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("ClientRefund approve failed: " . $e->getMessage());
            return false;
        }
    }

    /** Reject refund request */
    public function reject($id, $approverId)
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET status = 'rejected', approved_by = ?, approved_at = NOW()
             WHERE id = ? AND status = 'pending'"
        );
        $stmt->bind_param('ii', $approverId, $id);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    /** Link the auto-created cashout request to the refund */
    public function linkCashoutRequest($refundId, $cashoutRequestId)
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET cashout_request_id = ?
             WHERE id = ?"
        );
        $stmt->bind_param('ii', $cashoutRequestId, $refundId);
        return $stmt->execute();
    }

    /** Delete a refund request (only if pending) */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ? AND status = 'pending'");
        $stmt->bind_param('i', $id);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }
}
