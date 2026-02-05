// Fix the getRecentCashOutTransactions method
public function getRecentCashOutTransactions($limit = 10, $period = 'all_time', $projectId = null)
{
    $dateRange = $this->getDateRangeByPeriod($period);
    $startDate = $dateRange['start'];
    $endDate = $dateRange['end'];

    $sql = "
        SELECT 
            'management' as source,
            mco.id,
            mco.transaction_date as date,
            mco.role_name,
            mco.person_name,
            mco.paid_to,
            mco.amount,
            mco.payment_method,
            mco.payment_status,
            mco.description,
            NULL as project_id,
            NULL as project_name
        FROM management_cashout mco
        WHERE mco.transaction_date BETWEEN ? AND ?
        
        UNION ALL
        
        SELECT 
            'cashout_request' as source,
            cp.id,
            cp.payment_date as date,
            'Cashout Request' as role_name,
            u.full_name as person_name,
            cr.purpose_paid_to as paid_to,
            cp.amount,
            cp.payment_method,
            'paid' as payment_status,
            cr.description,
            cr.project_id,
            p.name as project_name
        FROM cashout_payments cp
        LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
        LEFT JOIN users u ON cr.requested_by = u.id
        LEFT JOIN projects p ON cr.project_id = p.id
        WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
        AND cp.payment_date BETWEEN ? AND ?
    ";
    
    if ($projectId) {
        $sql .= " AND (cr.project_id = ? OR cp.project_id = ?)";
    }

    $sql .= " ORDER BY date DESC LIMIT ?";

    $stmt = $this->db->prepare($sql);
    
    if ($projectId) {
        // When projectId is provided: startDate, endDate, startDate, endDate, projectId, projectId, limit
        $stmt->bind_param("ssssiii", $startDate, $endDate, $startDate, $endDate, $projectId, $projectId, $limit);
    } else {
        // When projectId is null: startDate, endDate, startDate, endDate, limit
        $stmt->bind_param("ssssi", $startDate, $endDate, $startDate, $endDate, $limit);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }

    $stmt->close();
    return $transactions;
}