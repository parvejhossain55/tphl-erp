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
        $sql = "SELECT oc.lead_id,
                   l.full_name AS client_name,
                   l.phone AS client_phone,
                   oc.email,
                   oc.login_email,
                   GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') AS project_names,
                   GROUP_CONCAT(DISTINCT CONCAT(f.flat_name, ' (', f.sqft, ' sqft)') SEPARATOR ', ') AS flat_names,
                   SUM(lp.amount) AS total_paid
            FROM onboard_clients AS oc
            LEFT JOIN lead_bookings AS lb ON lb.lead_id = oc.lead_id
            LEFT JOIN leads AS l ON l.id = oc.lead_id
            LEFT JOIN projects AS p ON lb.project_id = p.id
            LEFT JOIN flats AS f ON lb.flat_id = f.id
            LEFT JOIN lead_payments AS lp ON lp.booking_id = lb.id
            WHERE 1=1";

        $params = [];
        $types  = "";

        if ($period && isset($period['from']) && isset($period['to'])) {
            $sql .= " AND oc.created_at BETWEEN ? AND ?";
            $params[] = $period['from'];
            $params[] = $period['to'];
            $types .= "ss";
        }

        $sql .= " GROUP BY oc.id, oc.lead_id, oc.email, oc.login_email, l.full_name, l.phone
              ORDER BY l.full_name ASC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("OnboardClient::getAll prepare error: " . $this->db->error);
            return [];
        }
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
    // Create onboard client
    public function create($leadId, $email, $password, $links = [], $created_by = null, $login_email = null)
    {
        if (!$leadId) return false;

        // Check if lead already has login
        $checkSql = "SELECT id FROM onboard_clients WHERE lead_id = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bind_param("i", $leadId);
        $checkStmt->execute();
        $exists = $checkStmt->get_result()->fetch_assoc();
        if ($exists) return $exists['id'];

        // Links
        $cameraLink1        = $links['cameraLink1'] ?? null;
        $cameraLink2        = $links['cameraLink2'] ?? null;
        $moneyReceiptLink   = $links['moneyReceiptLink'] ?? null;
        $purchaseDetailsLink = $links['purchaseDetailsLink'] ?? null;

        // Insert login
        $sql = "INSERT INTO onboard_clients
        ( lead_id, email, login_email, password, cameraLink1, cameraLink2, moneyReceiptLink, purchaseDetailsLink, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $stmt->bind_param(
            "isssssssi",
            $leadId,
            $email,
            $login_email,
            $hashedPassword,
            $cameraLink1,
            $cameraLink2,
            $moneyReceiptLink,
            $purchaseDetailsLink,
            $created_by
        );

        return $stmt->execute() ? $this->db->insert_id : false;
    }

    // Fetch unique leads from bookings

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
        $login_email = $data['login_email'] ?? null;

        if ($password) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $sql = "UPDATE onboard_clients 
                SET email = ?, login_email = ?, password = ?, cameraLink1 = ?, cameraLink2 = ?, moneyReceiptLink = ?, purchaseDetailsLink = ?
                WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("sssssssi", $email, $login_email, $hashedPassword, $cameraLink1, $cameraLink2, $moneyReceiptLink, $purchaseDetailsLink, $id);
        } else {
            // Do not update password if blank
            $sql = "UPDATE onboard_clients 
                SET email = ?, login_email = ?, cameraLink1 = ?, cameraLink2 = ?, moneyReceiptLink = ?, purchaseDetailsLink = ?
                WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ssssssi", $email, $login_email, $cameraLink1, $cameraLink2, $moneyReceiptLink, $purchaseDetailsLink, $id);
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
