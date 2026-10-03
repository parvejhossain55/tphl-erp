<?php

class EmployeeKPIService
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Calculate Sales KPI for a specific user and target cycle.
     * Returns an array with individual scores, targets, actuals, and total score out of 100%.
     */
    public function calculateSalesKPI($userId, $targetId = null)
    {
        $kpi = [
            'revenue_generation' => ['target' => 0, 'actual' => 0, 'score' => 0, 'max' => 30],
            'target_vs_achievement' => ['target' => 0, 'actual' => 0, 'score' => 0, 'max' => 20],
            'unit_sold' => ['target' => 0, 'actual' => 0, 'score' => 0, 'max' => 10],
            'highest_visit' => ['target' => 0, 'actual' => 0, 'score' => 0, 'max' => 10],
            'sales_cvr' => ['target' => 0, 'actual' => 0, 'score' => 0, 'max' => 10],
            'leads_connected' => ['target' => 0, 'actual' => 0, 'score' => 0, 'max' => 10],
            'data_quality' => ['target' => 0, 'actual' => 0, 'score' => 0, 'max' => 5],
            'hr' => ['target' => 0, 'actual' => 0, 'score' => 0, 'max' => 5],
            'total_score' => 0
        ];

        // 1. Get the sales target
        $targetQuery = "";
        if ($targetId) {
            $targetQuery = "SELECT * FROM sales_targets WHERE id = " . intval($targetId) . " AND sales_user_id = " . intval($userId);
        } else {
            // Get current active target (e.g. based on current date)
            $currentDate = date('Y-m-d');
            $targetQuery = "SELECT * FROM sales_targets WHERE sales_user_id = " . intval($userId) . " AND from_date <= '$currentDate' AND to_date >= '$currentDate' ORDER BY id DESC LIMIT 1";
        }
        
        $res = $this->db->query($targetQuery);
        if (!$res || $res->num_rows == 0) {
            // Fallback to the latest target if no active one found
            $targetQuery = "SELECT * FROM sales_targets WHERE sales_user_id = " . intval($userId) . " ORDER BY id DESC LIMIT 1";
            $res = $this->db->query($targetQuery);
        }
        
        if (!$res || $res->num_rows == 0) {
            return $kpi; // No targets set, returning 0s
        }
        
        $targetData = $res->fetch_assoc();
        $fromDate = $targetData['from_date'] . ' 00:00:00';
        $toDate = $targetData['to_date'] . ' 23:59:59';
        
        
        // ---------------------------------------------------------
        // 1. Revenue Generation (30%)
        // Target: booking_value_target
        // Actual: Total verified collections in date range
        // ---------------------------------------------------------
        $kpi['revenue_generation']['target'] = (float)$targetData['booking_value_target'];
        
        $revQuery = "SELECT SUM(amount) as total_collection FROM lead_payments 
                     WHERE created_by = " . intval($userId) . " 
                     AND payment_date BETWEEN '$fromDate' AND '$toDate'
                     AND verification_status = 'verified' AND is_trashed = 0";
        $revRes = $this->db->query($revQuery);
        if ($revRes && $revRow = $revRes->fetch_assoc()) {
            $kpi['revenue_generation']['actual'] = (float)$revRow['total_collection'];
        }
        
        if ($kpi['revenue_generation']['target'] > 0) {
            $score = ($kpi['revenue_generation']['actual'] / $kpi['revenue_generation']['target']) * $kpi['revenue_generation']['max'];
            $kpi['revenue_generation']['score'] = min($kpi['revenue_generation']['max'], max(0, $score));
        }

        // ---------------------------------------------------------
        // 2. Target vs Achievement Value (20%)
        // Target: booking_value_target
        // Actual: Total value of flats booked in date range
        // ---------------------------------------------------------
        $kpi['target_vs_achievement']['target'] = (float)$targetData['booking_value_target'];
        
        $achvQuery = "SELECT SUM(f.land_share_value) as total_booked_value 
                      FROM lead_bookings lb
                      JOIN flats f ON f.id = lb.flat_id
                      WHERE lb.sales_by_id = " . intval($userId) . " 
                      AND lb.deleted_at IS NULL
                      AND lb.booking_date BETWEEN '$fromDate' AND '$toDate'";
        $achvRes = $this->db->query($achvQuery);
        if ($achvRes && $achvRow = $achvRes->fetch_assoc()) {
            $kpi['target_vs_achievement']['actual'] = (float)$achvRow['total_booked_value'];
        }
        
        if ($kpi['target_vs_achievement']['target'] > 0) {
            $score = ($kpi['target_vs_achievement']['actual'] / $kpi['target_vs_achievement']['target']) * $kpi['target_vs_achievement']['max'];
            $kpi['target_vs_achievement']['score'] = min($kpi['target_vs_achievement']['max'], max(0, $score));
        }

        // ---------------------------------------------------------
        // 3. No. of Unit Sold (10%)
        // Target: booking_target
        // Actual: Count of booked units
        // ---------------------------------------------------------
        $kpi['unit_sold']['target'] = (int)$targetData['booking_target'];
        
        $unitQuery = "SELECT COUNT(id) as total_units 
                      FROM lead_bookings 
                      WHERE sales_by_id = " . intval($userId) . " 
                      AND deleted_at IS NULL
                      AND booking_date BETWEEN '$fromDate' AND '$toDate'";
        $unitRes = $this->db->query($unitQuery);
        if ($unitRes && $unitRow = $unitRes->fetch_assoc()) {
            $kpi['unit_sold']['actual'] = (int)$unitRow['total_units'];
        }
        
        if ($kpi['unit_sold']['target'] > 0) {
            $score = ($kpi['unit_sold']['actual'] / $kpi['unit_sold']['target']) * $kpi['unit_sold']['max'];
            $kpi['unit_sold']['score'] = min($kpi['unit_sold']['max'], max(0, $score));
        }

        // ---------------------------------------------------------
        // 4. Highest Visit (Site Visit Ratio) (10%)
        // Target: site_visit_leads_target
        // Actual: Number of visits conducted
        // ---------------------------------------------------------
        $kpi['highest_visit']['target'] = (int)$targetData['site_visit_leads_target'];
        
        $visitQuery = "SELECT COUNT(DISTINCT lead_id) as total_visits 
                       FROM lead_follow_ups 
                       WHERE created_by = " . intval($userId) . " 
                       AND status = 'visited' 
                       AND created_at BETWEEN '$fromDate' AND '$toDate'";
        $visitRes = $this->db->query($visitQuery);
        if ($visitRes && $visitRow = $visitRes->fetch_assoc()) {
            $kpi['highest_visit']['actual'] = (int)$visitRow['total_visits'];
        }
        
        if ($kpi['highest_visit']['target'] > 0) {
            $score = ($kpi['highest_visit']['actual'] / $kpi['highest_visit']['target']) * $kpi['highest_visit']['max'];
            $kpi['highest_visit']['score'] = min($kpi['highest_visit']['max'], max(0, $score));
        }

        // ---------------------------------------------------------
        // 5. Sales CVR (Min 1%) (10%)
        // Conversion rate: (Sales / Leads Assigned) * 100
        // Target: 1%
        // ---------------------------------------------------------
        $kpi['sales_cvr']['target'] = 1; // 1%
        
        $leadsQuery = "SELECT COUNT(id) as total_assigned 
                       FROM leads 
                       WHERE assigned_id = " . intval($userId) . " 
                       AND (DATE(assigned_at) BETWEEN '$fromDate' AND '$toDate' OR (assigned_at IS NULL AND DATE(created_at) BETWEEN '$fromDate' AND '$toDate'))";
        $leadsRes = $this->db->query($leadsQuery);
        $totalAssigned = 0;
        if ($leadsRes && $leadsRow = $leadsRes->fetch_assoc()) {
            $totalAssigned = (int)$leadsRow['total_assigned'];
        }
        
        $cvr = 0;
        if ($totalAssigned > 0) {
            $cvr = ($kpi['unit_sold']['actual'] / $totalAssigned) * 100;
        }
        $kpi['sales_cvr']['actual'] = round($cvr, 2);
        
        if ($cvr >= 1) {
            $kpi['sales_cvr']['score'] = 10;
        } else {
            $kpi['sales_cvr']['score'] = ($cvr / 1) * 10;
        }

        // ---------------------------------------------------------
        // 6. Leads Connected & Follow-up (10%)
        // Actual logic: (Contacted / Assigned) * 10
        // Placeholder for now.
        // ---------------------------------------------------------
        $kpi['leads_connected']['target'] = $totalAssigned;
        $contactedQuery = "SELECT COUNT(DISTINCT lfu.lead_id) as total_contacted 
                           FROM lead_follow_ups lfu
                           JOIN leads l ON l.id = lfu.lead_id
                           WHERE l.assigned_id = " . intval($userId) . " 
                           AND lfu.created_by = " . intval($userId) . "
                           AND lfu.created_at BETWEEN '$fromDate' AND '$toDate'";
        $contactedRes = $this->db->query($contactedQuery);
        if ($contactedRes && $contactedRow = $contactedRes->fetch_assoc()) {
            $kpi['leads_connected']['actual'] = (int)$contactedRow['total_contacted'];
        }
        
        if ($totalAssigned > 0) {
            $score = ($kpi['leads_connected']['actual'] / $totalAssigned) * $kpi['leads_connected']['max'];
            $kpi['leads_connected']['score'] = min($kpi['leads_connected']['max'], max(0, $score));
        } else {
            $kpi['leads_connected']['score'] = $kpi['leads_connected']['max']; // 100% if no leads assigned
        }

        // ---------------------------------------------------------
        // 7. Data Quality & Timeliness (5%)
        // Placeholder. Default to max for now or calculate based on empty fields.
        // ---------------------------------------------------------
        $kpi['data_quality']['target'] = 100; // 100% accurate
        $kpi['data_quality']['actual'] = 100; 
        $kpi['data_quality']['score'] = 5;

        // ---------------------------------------------------------
        // 8. HR (5%)
        // Placeholder for attendance / task completion.
        // ---------------------------------------------------------
        $kpi['hr']['target'] = 100; 
        $kpi['hr']['actual'] = 100; 
        $kpi['hr']['score'] = 5;

        // Sum up total score
        foreach ($kpi as $key => $metric) {
            if (is_array($metric) && isset($metric['score'])) {
                $kpi['total_score'] += $metric['score'];
            }
        }
        
        // Round scores
        foreach ($kpi as $key => &$metric) {
            if (is_array($metric)) {
                $metric['score'] = round($metric['score'], 2);
            }
        }
        $kpi['total_score'] = round($kpi['total_score'], 2);

        return $kpi;
    }
}
