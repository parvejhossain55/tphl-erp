<?php
class CedProjectAssignment
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Get all active CED agents (Role 7, 37, 39)
     */
    public function getAvailableCedAgents(): array
    {
        $sql = "SELECT u.id, u.full_name, u.email, u.phone, u.photo, u.role_id, r.name AS role_name
                FROM users u
                INNER JOIN roles r ON u.role_id = r.id
                WHERE (u.role_id IN (7, 37, 39) OR u.secondary_role_id IN (7, 37, 39))
                ORDER BY r.id DESC, u.full_name ASC";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get all active projects
     */
    public function getAllProjects(): array
    {
        $sql = "SELECT id, name, location_id, project_sale_status 
                FROM projects 
                WHERE deleted_at IS NULL 
                ORDER BY name ASC";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get assigned project IDs for a specific agent
     */
    public function getAgentAssignedProjectIds(int $userId): array
    {
        $sql = "SELECT project_id FROM ced_project_assignments WHERE user_id = ? AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $ids = [];
        while ($row = $res->fetch_assoc()) {
            $ids[] = (int)$row['project_id'];
        }
        $stmt->close();
        return $ids;
    }

    /**
     * Save/sync assigned projects for an agent
     */
    public function saveAgentProjectAssignments(int $userId, array $projectIds, int $assignedBy): bool
    {
        $this->db->begin_transaction();
        try {
            // Deactivate existing assignments
            $delStmt = $this->db->prepare("DELETE FROM ced_project_assignments WHERE user_id = ?");
            $delStmt->bind_param("i", $userId);
            $delStmt->execute();
            $delStmt->close();

            if (!empty($projectIds)) {
                $insStmt = $this->db->prepare("INSERT INTO ced_project_assignments (user_id, project_id, assigned_by, status) VALUES (?, ?, ?, 'active')");
                foreach ($projectIds as $pId) {
                    $pId = (int)$pId;
                    if ($pId > 0) {
                        $insStmt->bind_param("iii", $userId, $pId, $assignedBy);
                        $insStmt->execute();
                    }
                }
                $insStmt->close();
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error saving CED project assignments: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get portfolio summary for all CED agents or a specific agent (optionally scoped to a single project)
     */
    public function getPortfolioSummary(?string $search = null, ?int $targetUserId = null, ?int $targetProjectId = null): array
    {
        $whereClauses = ["(u.role_id IN (7, 37, 39) OR u.secondary_role_id IN (7, 37, 39))"];
        $params = [];
        $types = "";

        if ($targetUserId !== null && $targetUserId > 0) {
            $whereClauses[] = "u.id = ?";
            $params[] = $targetUserId;
            $types .= "i";
        }

        if (!empty($search)) {
            $esSearch = "%" . trim($search) . "%";
            $whereClauses[] = "(u.full_name LIKE ? OR u.phone LIKE ? OR u.email LIKE ? OR p.name LIKE ?)";
            $params[] = $esSearch;
            $params[] = $esSearch;
            $params[] = $esSearch;
            $params[] = $esSearch;
            $types .= "ssss";
        }

        $whereSql = implode(" AND ", $whereClauses);

        // Fetch agents and their assigned project IDs & names
        $sql = "SELECT 
                    u.id AS agent_id,
                    u.full_name AS agent_name,
                    u.phone AS agent_phone,
                    u.email AS agent_email,
                    u.photo AS agent_photo,
                    u.role_id,
                    r.name AS role_name,
                    GROUP_CONCAT(DISTINCT p.id ORDER BY p.name SEPARATOR ',') AS assigned_project_ids,
                    GROUP_CONCAT(DISTINCT p.name ORDER BY p.name SEPARATOR '||') AS assigned_project_names
                FROM users u
                INNER JOIN roles r ON u.role_id = r.id
                LEFT JOIN ced_project_assignments cpa ON u.id = cpa.user_id AND cpa.status = 'active'
                LEFT JOIN projects p ON cpa.project_id = p.id AND p.deleted_at IS NULL
                WHERE $whereSql
                GROUP BY u.id
                ORDER BY r.id DESC, u.full_name ASC";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = $this->db->query($sql);
        }

        if (!$res) return [];

        $agents = $res->fetch_all(MYSQLI_ASSOC);
        if ($stmt ?? false) $stmt->close();

        // Calculate performance metrics for each agent based on their assigned projects
        foreach ($agents as &$agent) {
            $projectIdsStr = trim($agent['assigned_project_ids'] ?? '');
            $projectNamesStr = trim($agent['assigned_project_names'] ?? '');

            $projectIds = $projectIdsStr !== '' ? array_map('intval', explode(',', $projectIdsStr)) : [];
            $projectNames = $projectNamesStr !== '' ? explode('||', $projectNamesStr) : [];

            $agent['projects_count'] = count($projectIds);
            $agent['projects_list']  = [];
            for ($i = 0; $i < count($projectIds); $i++) {
                if (isset($projectNames[$i])) {
                    $agent['projects_list'][] = [
                        'id' => $projectIds[$i],
                        'name' => $projectNames[$i]
                    ];
                }
            }

            if ($targetProjectId !== null && $targetProjectId > 0) {
                $projectIds = in_array($targetProjectId, $projectIds, true) ? [$targetProjectId] : [];
                $agent['projects_list'] = array_values(array_filter($agent['projects_list'], fn($p) => (int)$p['id'] === $targetProjectId));
                $agent['projects_count'] = count($agent['projects_list']);
            }

            // Defaults
            $agent['total_clients']       = 0;
            $agent['project_memberships_count'] = 0;
            $agent['total_units']         = 0;
            $agent['total_price_value']   = 0.0;
            $agent['total_collected']     = 0.0;
            $agent['verified_collected']  = 0.0;
            $agent['unverified_collected'] = 0.0;
            $agent['total_overdue']       = 0.0;
            $agent['total_outstanding']   = 0.0;
            $agent['collection_rate']     = 0.0;

            if (!empty($projectIds)) {
                $pIdList = implode(',', $projectIds);

                // 1. Total unique clients & Project-client memberships & Total units & Total Price Value
                $q1 = $this->db->query("SELECT 
                        COUNT(DISTINCT lb.lead_id) AS total_clients,
                        COUNT(DISTINCT CONCAT(lb.project_id, '_', lb.lead_id)) AS project_memberships_count,
                        COUNT(lb.id) AS total_units,
                        COALESCE(SUM(COALESCE(f.price_value, lb.agreement_value, 0)), 0) AS total_price_value
                    FROM lead_bookings lb
                    LEFT JOIN flats f ON lb.flat_id = f.id
                    WHERE lb.project_id IN ($pIdList)
                      AND lb.deleted_at IS NULL
                      AND lb.booking_status NOT IN ('cancelled', 'refunded')
                      AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
                      AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))");

                if ($q1 && $row1 = $q1->fetch_assoc()) {
                    $agent['total_clients']             = (int)$row1['total_clients'];
                    $agent['project_memberships_count'] = (int)$row1['project_memberships_count'];
                    $agent['total_units']               = (int)$row1['total_units'];
                    $agent['total_price_value']         = (float)$row1['total_price_value'];
                }

                // 2. Collections for these projects (Total, Verified, Unverified) matching AccountClient logic
                $q2 = $this->db->query("SELECT 
                        COALESCE(SUM(CASE WHEN (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                               AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') 
                                          THEN lp.amount ELSE 0 END), 0) AS total_collected,
                        COALESCE(SUM(CASE WHEN (lp.payment_status IN ('paid', 'partially_paid')) 
                                               AND lp.verification_status = 'verified' 
                                          THEN lp.amount ELSE 0 END), 0) AS verified_collected,
                        COALESCE(SUM(CASE WHEN (lp.payment_status IN ('paid', 'partially_paid', 'pending')) 
                                               AND (lp.verification_status IS NULL OR lp.verification_status != 'verified') 
                                               AND lp.verification_status != 'rejected' 
                                          THEN lp.amount ELSE 0 END), 0) AS unverified_collected
                    FROM lead_payments lp
                    INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
                    WHERE lb.project_id IN ($pIdList)
                      AND lb.deleted_at IS NULL
                      AND lb.booking_status NOT IN ('cancelled', 'refunded')
                      AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
                      AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
                      AND lp.is_trashed = 0
                      AND COALESCE(lp.is_emi_discount, 0) = 0");

                if ($q2 && $row2 = $q2->fetch_assoc()) {
                    $agent['total_collected']      = (float)$row2['total_collected'];
                    $agent['verified_collected']   = (float)$row2['verified_collected'];
                    $agent['unverified_collected'] = (float)$row2['unverified_collected'];
                }

                // 3. Total Matured Overdue EMIs for these projects
                $q3 = $this->db->query("SELECT 
                        COALESCE(SUM(es.due_emi_amount), 0) AS total_overdue
                    FROM emi_schedules es
                    INNER JOIN lead_bookings lb ON es.lead_booking_id = lb.id
                    WHERE lb.project_id IN ($pIdList)
                      AND lb.deleted_at IS NULL
                      AND lb.booking_status NOT IN ('cancelled', 'refunded')
                      AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
                      AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
                      AND es.due_date <= CURDATE()
                      AND es.status != 'paid'");

                if ($q3 && $row3 = $q3->fetch_assoc()) {
                    $agent['total_overdue'] = (float)$row3['total_overdue'];
                }

                // Outstanding Due = Total Value - Total Collected
                $agent['total_outstanding'] = max(0, $agent['total_price_value'] - $agent['total_collected']);

                if ($agent['total_price_value'] > 0) {
                    $agent['collection_rate'] = round(($agent['total_collected'] / $agent['total_price_value']) * 100, 1);
                }
            }
        }
        unset($agent);

        // Sort agents by collection_rate DESC (highest collection & least due ratio), then total_collected DESC
        usort($agents, function($a, $b) {
            if ($a['projects_count'] === 0 && $b['projects_count'] === 0) return 0;
            if ($a['projects_count'] === 0) return 1;
            if ($b['projects_count'] === 0) return -1;
            if ($b['collection_rate'] != $a['collection_rate']) {
                return $b['collection_rate'] <=> $a['collection_rate'];
            }
            return $b['total_collected'] <=> $a['total_collected'];
        });

        // Assign rank numbers and comparison metrics
        $currentRank = 1;
        foreach ($agents as &$agent) {
            if ($agent['projects_count'] > 0) {
                $agent['rank'] = $currentRank++;
                $agent['due_rate'] = round(max(0, 100 - $agent['collection_rate']), 1);
            } else {
                $agent['rank'] = null;
                $agent['due_rate'] = 0.0;
            }
        }
        unset($agent);

        return $agents;
    }

    /**
     * Get detailed breakdown per assigned project for an agent (with clients & units taken)
     */
    public function getAgentProjectDetails(int $userId, ?int $filterProjectId = null): array
    {
        $assignedProjectIds = $this->getAgentAssignedProjectIds($userId);
        if (empty($assignedProjectIds)) {
            return [];
        }

        if ($filterProjectId !== null && $filterProjectId > 0) {
            if (!in_array($filterProjectId, $assignedProjectIds, true)) {
                return [];
            }
            $targetProjectIds = [$filterProjectId];
        } else {
            $targetProjectIds = $assignedProjectIds;
        }

        $pIdList = implode(',', $targetProjectIds);

        // Find multi-project clients across all assigned projects for this agent
        $multiProjectLeads = [];
        $leadProjectsMap = [];
        $leadProjSql = "SELECT lb.lead_id, lb.project_id, p.name AS project_name
                        FROM lead_bookings lb
                        INNER JOIN projects p ON lb.project_id = p.id
                        WHERE lb.project_id IN ($pIdList)
                          AND lb.deleted_at IS NULL
                          AND lb.booking_status NOT IN ('cancelled', 'refunded')
                          AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
                          AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
                        GROUP BY lb.lead_id, lb.project_id";
        $lpRes = $this->db->query($leadProjSql);
        if ($lpRes) {
            while ($lpRow = $lpRes->fetch_assoc()) {
                $lid = (int)$lpRow['lead_id'];
                $leadProjectsMap[$lid][] = $lpRow['project_name'];
            }
            foreach ($leadProjectsMap as $lid => $projList) {
                if (count($projList) > 1) {
                    $multiProjectLeads[$lid] = $projList;
                }
            }
        }

        // Fetch projects summary
        $pSql = "SELECT id, name, location_id, project_sale_status FROM projects WHERE id IN ($pIdList) ORDER BY name ASC";
        $pRes = $this->db->query($pSql);
        if (!$pRes) return [];

        $projects = $pRes->fetch_all(MYSQLI_ASSOC);
        $projectDetails = [];

        foreach ($projects as $proj) {
            $pId = (int)$proj['id'];

            // Fetch all client bookings under this project
            $bSql = "SELECT 
                        lb.id AS booking_id,
                        lb.booking_date,
                        lb.booking_status,
                        l.id AS lead_id,
                        l.full_name AS client_name,
                        COALESCE(NULLIF(TRIM(l.phone), ''), TRIM(l.phone_number)) AS client_phone,
                        l.email AS client_email,
                        f.id AS flat_id,
                        f.flat_name AS unit_name,
                        COALESCE(f.price_value, lb.agreement_value, 0) AS unit_price,
                        COALESCE(pay.paid, 0) AS unit_paid,
                        COALESCE(pay.paid_verified, 0) AS unit_paid_verified,
                        COALESCE(pay.paid_unverified, 0) AS unit_paid_unverified,
                        COALESCE(due.overdue, 0) AS unit_overdue
                    FROM lead_bookings lb
                    INNER JOIN leads l ON lb.lead_id = l.id
                    LEFT JOIN flats f ON lb.flat_id = f.id
                    LEFT JOIN (
                        SELECT 
                            booking_id, 
                            SUM(CASE WHEN (payment_status IN ('paid', 'partially_paid', 'pending')) AND (verification_status IS NULL OR verification_status != 'rejected') THEN amount ELSE 0 END) AS paid,
                            SUM(CASE WHEN (payment_status IN ('paid', 'partially_paid')) AND verification_status = 'verified' THEN amount ELSE 0 END) AS paid_verified,
                            SUM(CASE WHEN (payment_status IN ('paid', 'partially_paid', 'pending')) AND (verification_status IS NULL OR verification_status != 'verified') AND verification_status != 'rejected' THEN amount ELSE 0 END) AS paid_unverified
                        FROM lead_payments
                        WHERE is_trashed = 0
                          AND COALESCE(is_emi_discount, 0) = 0
                        GROUP BY booking_id
                    ) pay ON pay.booking_id = lb.id
                    LEFT JOIN (
                        SELECT lead_booking_id, SUM(due_emi_amount) AS overdue
                        FROM emi_schedules
                        WHERE due_date <= CURDATE() AND status != 'paid'
                        GROUP BY lead_booking_id
                    ) due ON due.lead_booking_id = lb.id
                    WHERE lb.project_id = $pId
                      AND lb.deleted_at IS NULL
                      AND lb.booking_status NOT IN ('cancelled', 'refunded')
                      AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
                      AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
                    ORDER BY l.full_name ASC, lb.id ASC";

            $bRes = $this->db->query($bSql);
            $bookings = $bRes ? $bRes->fetch_all(MYSQLI_ASSOC) : [];

            // Group by client
            $clientsMap = [];
            $projectTotalPrice = 0.0;
            $projectTotalPaid = 0.0;
            $projectTotalPaidVerified = 0.0;
            $projectTotalPaidUnverified = 0.0;
            $projectTotalOverdue = 0.0;

            foreach ($bookings as $b) {
                $leadId = (int)$b['lead_id'];
                $uPrice = (float)$b['unit_price'];
                $uPaid = (float)$b['unit_paid'];
                $uPaidVerified = (float)$b['unit_paid_verified'];
                $uPaidUnverified = (float)$b['unit_paid_unverified'];
                $uOverdue = (float)$b['unit_overdue'];

                $projectTotalPrice += $uPrice;
                $projectTotalPaid   += $uPaid;
                $projectTotalPaidVerified += $uPaidVerified;
                $projectTotalPaidUnverified += $uPaidUnverified;
                $projectTotalOverdue += $uOverdue;

                if (!isset($clientsMap[$leadId])) {
                    $otherProjects = isset($multiProjectLeads[$leadId])
                        ? array_values(array_filter($multiProjectLeads[$leadId], fn($pName) => $pName !== $proj['name']))
                        : [];

                    $clientsMap[$leadId] = [
                        'lead_id'          => $leadId,
                        'client_name'      => $b['client_name'],
                        'client_phone'     => $b['client_phone'],
                        'client_email'     => $b['client_email'],
                        'is_multi_project' => !empty($otherProjects),
                        'other_projects'   => $otherProjects,
                        'units_count'      => 0,
                        'units'            => [],
                        'total_price'      => 0.0,
                        'total_paid'       => 0.0,
                        'paid_verified'    => 0.0,
                        'paid_unverified'  => 0.0,
                        'total_overdue'    => 0.0,
                        'balance_due'      => 0.0
                    ];
                }

                $clientsMap[$leadId]['units_count']++;
                $clientsMap[$leadId]['units'][] = [
                    'booking_id'      => $b['booking_id'],
                    'unit_name'       => $b['unit_name'] ?: 'Unit #' . $b['booking_id'],
                    'unit_price'      => $uPrice,
                    'unit_paid'       => $uPaid,
                    'unit_paid_verified' => $uPaidVerified,
                    'unit_paid_unverified' => $uPaidUnverified,
                    'unit_due'        => max(0, $uPrice - $uPaid),
                    'unit_overdue'    => $uOverdue,
                    'status'          => $b['booking_status']
                ];
                $clientsMap[$leadId]['total_price']       += $uPrice;
                $clientsMap[$leadId]['total_paid']        += $uPaid;
                $clientsMap[$leadId]['paid_verified']     += $uPaidVerified;
                $clientsMap[$leadId]['paid_unverified']   += $uPaidUnverified;
                $clientsMap[$leadId]['total_overdue']     += $uOverdue;
                $clientsMap[$leadId]['balance_due']       = max(0, $clientsMap[$leadId]['total_price'] - $clientsMap[$leadId]['total_paid']);
            }

            $projCollRate = $projectTotalPrice > 0 ? round(($projectTotalPaid / $projectTotalPrice) * 100, 1) : 0.0;
            $projDueRate = round(max(0, 100 - $projCollRate), 1);

            $projectDetails[] = [
                'project_id'              => $pId,
                'project_name'            => $proj['name'],
                'total_clients_count'     => count($clientsMap),
                'total_units_count'       => count($bookings),
                'project_total_price'     => $projectTotalPrice,
                'project_total_paid'      => $projectTotalPaid,
                'project_paid_verified'   => $projectTotalPaidVerified,
                'project_paid_unverified' => $projectTotalPaidUnverified,
                'project_total_overdue'   => $projectTotalOverdue,
                'project_balance_due'     => max(0, $projectTotalPrice - $projectTotalPaid),
                'project_collection_rate' => $projCollRate,
                'project_due_rate'        => $projDueRate,
                'clients'                 => array_values($clientsMap)
            ];
        }

        // Sort projects by collection efficiency (highest collection % & least due ratio first)
        usort($projectDetails, function($a, $b) {
            if ($b['project_collection_rate'] != $a['project_collection_rate']) {
                return $b['project_collection_rate'] <=> $a['project_collection_rate'];
            }
            return $b['project_total_paid'] <=> $a['project_total_paid'];
        });

        $pRank = 1;
        foreach ($projectDetails as &$pd) {
            $pd['project_rank'] = $pRank++;
        }
        unset($pd);

        return $projectDetails;
    }
}

