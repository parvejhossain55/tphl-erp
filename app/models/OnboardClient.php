<?php

class OnboardClient extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }

public function getAll($period = null)
{
    $sql = "SELECT oc.*, 
                   p.name AS project_name,
                   f.flat_name,
                   f.sqft,
                   f.price_value,
                   lb.booking_status,
                   lb.agreement_value,
                   l.full_name AS client_name,
                   l.phone AS client_phone,
                   l.id as client_id,
                   lp.amount,
                   lp.created_at AS booking_pay_date
            FROM onboard_clients AS oc
            LEFT JOIN lead_bookings AS lb ON oc.lead_booking_id = lb.id
            LEFT JOIN leads AS l ON lb.lead_id = l.id
            LEFT JOIN projects AS p ON oc.project_id = p.id
            LEFT JOIN flats AS f ON oc.flat_id = f.id
            LEFT JOIN lead_payments AS lp ON lp.booking_id = lb.id
            WHERE 1 = 1"; // always true for easier AND conditions

    $params = [];
    $types  = "";

    if ($period && isset($period['from']) && isset($period['to'])) {
        $sql .= " AND oc.created_at BETWEEN ? AND ?";
        $params[] = $period['from'];
        $params[] = $period['to'];
        $types .= "ss";
    }

    $stmt = $this->db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}


    /**
     * Create a new onboard client
     * @param int $leadBookingId
     * @param string $email
     * @param string $password
     * @param array $links Optional camera/money/purchase links
     */
    public function create($leadBookingId, $email, $password, $links = [], $created_by = null)
    {
        // Fetch lead_id and project_id from lead_bookings
        $sqlBooking = "SELECT lead_id, project_id,flat_id FROM lead_bookings WHERE id = ?";
        $stmtBooking = $this->db->prepare($sqlBooking);
        $stmtBooking->bind_param("i", $leadBookingId);
        $stmtBooking->execute();
        $resultBooking = $stmtBooking->get_result();
        $bookingData = $resultBooking->fetch_assoc();

        $leadId = $bookingData['lead_id'] ?? null;
        $projectId = $bookingData['project_id'] ?? null;
        $flatId = $bookingData['flat_id'] ?? null;

        $cameraLink1 = $links['cameraLink1'] ?? null;
        $cameraLink2 = $links['cameraLink2'] ?? null;
        $moneyReceiptLink = $links['moneyReceiptLink'] ?? null;
        $purchaseDetailsLink = $links['purchaseDetailsLink'] ?? null;

        $sql = "INSERT INTO onboard_clients 
            (lead_booking_id, lead_id, project_id, email, password, cameraLink1, cameraLink2, moneyReceiptLink, purchaseDetailsLink, created_by,flat_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";

        $stmt = $this->db->prepare($sql);
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $stmt->bind_param(
            "iiissssssii",
            $leadBookingId,
            $leadId,
            $projectId,
            $email,
            $hashedPassword,
            $cameraLink1,
            $cameraLink2,
            $moneyReceiptLink,
            $purchaseDetailsLink,
            $created_by,
            $flatId
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        } else {
            return false;
        }
    }
    public function find($id)
    {


        $stmt = $this->db->prepare(
            "SELECT * From
           onboard_clients
        WHERE onboard_clients.id = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Update lead
     */
public function update($id, $data)
{
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? null;
    $cameraLink1 = $data['cameraLink1'] ?? null;
    $cameraLink2 = $data['cameraLink2'] ?? null;
    $moneyReceiptLink = $data['moneyReceiptLink'] ?? null;
    $purchaseDetailsLink = $data['purchaseDetailsLink'] ?? null;

    if ($password) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $sql = "UPDATE onboard_clients 
                SET email = ?, password = ?, cameraLink1 = ?, cameraLink2 = ?, moneyReceiptLink = ?, purchaseDetailsLink = ?
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssssssi", $email, $hashedPassword, $cameraLink1, $cameraLink2, $moneyReceiptLink, $purchaseDetailsLink, $id);
    } else {
        // Do not update password if blank
        $sql = "UPDATE onboard_clients 
                SET email = ?, cameraLink1 = ?, cameraLink2 = ?, moneyReceiptLink = ?, purchaseDetailsLink = ?
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sssssi", $email, $cameraLink1, $cameraLink2, $moneyReceiptLink, $purchaseDetailsLink, $id);
    }

    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}


    public function countLeadsByMonth($year, $month)
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*) AS total
        FROM leads
        WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?
    ");
        $stmt->bind_param("ii", $year, $month);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int)($result['total'] ?? 0);
    }


    public function updateLastLeadAction($leadId, $lastAction)
    {
        $allowedActions = ['not_called', 'calling', 'called', 'visited'];

        if (!in_array($lastAction, $allowedActions)) {
            return false;
        }

        $sql = "UPDATE leads 
            SET last_lead_action = ?, 
                updated_at = NOW()
            WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $lastAction, $leadId);

        return $stmt->execute();
    }


    public function softDelete($leadId)
    {
        $this->db->begin_transaction();

        try {
            $now = date('Y-m-d H:i:s');

            // 1. Leads table (also update status)
            $stmt = $this->db->prepare("
            UPDATE leads 
            SET deleted_at = ?, status = 'inactive'
            WHERE id = ?
        ");
            $stmt->bind_param("si", $now, $leadId);
            $stmt->execute();
            $stmt->close();

            // 2. Lead bookings
            $stmt = $this->db->prepare("
            UPDATE lead_bookings 
            SET deleted_at = ?
            WHERE lead_id = ?
        ");
            $stmt->bind_param("si", $now, $leadId);
            $stmt->execute();
            $stmt->close();

            // 3. Lead follow ups
            $stmt = $this->db->prepare("
            UPDATE lead_follow_ups 
            SET deleted_at = ?
            WHERE lead_id = ?
        ");
            $stmt->bind_param("si", $now, $leadId);
            $stmt->execute();
            $stmt->close();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    // user for dashboard 

}
