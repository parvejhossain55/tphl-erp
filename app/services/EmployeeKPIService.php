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
    public function calculateSalesKPI($userId, $targetId = null, $tipsoiStat = [], $filterFromDate = null, $filterToDate = null)
    {
        $kpi = [
            'revenue_generation' => ['target' => 0, 'actual' => 0, 'score' => 0, 'max' => 30, 'details' => ''],
            'target_vs_achievement' => ['target' => 0, 'actual' => 0, 'score' => 0, 'max' => 20, 'details' => ''],
            'unit_sold' => ['target' => 0, 'actual' => 0, 'score' => 0, 'max' => 10, 'details' => ''],
            'highest_visit' => ['target' => 0, 'actual' => 0, 'score' => 0, 'max' => 10, 'details' => ''],
            'sales_cvr' => ['target' => 0, 'actual' => 0, 'score' => 0, 'max' => 10, 'details' => ''],
            'leads_connected' => ['target' => 0, 'actual' => 0, 'score' => 0, 'max' => 10, 'details' => ''],
            'data_quality' => ['target' => 0, 'actual' => 0, 'score' => 0, 'max' => 5, 'details' => ''],
            'hr' => ['target' => 0, 'actual' => 0, 'score' => 0, 'max' => 5, 'details' => ''],
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
        
        $targetData = [];
        if ($res && $res->num_rows > 0) {
            $targetData = $res->fetch_assoc();
        }
        
        // Use filter dates if provided; otherwise fallback to target dates; otherwise fallback to current month
        if ($filterFromDate && $filterToDate) {
            $fromDate = $filterFromDate . ' 00:00:00';
            $toDate = $filterToDate . ' 23:59:59';
        } elseif (!empty($targetData)) {
            $fromDate = $targetData['from_date'] . ' 00:00:00';
            $toDate = $targetData['to_date'] . ' 23:59:59';
        } else {
            $fromDate = date('Y-m-01') . ' 00:00:00';
            $toDate = date('Y-m-t') . ' 23:59:59';
        }
        
        
        // ---------------------------------------------------------
        // 1. Revenue Generation (30%)
        // Target: booking_value_target
        // Actual: Total verified collections in date range
        // ---------------------------------------------------------
        $kpi['revenue_generation']['target'] = isset($targetData['booking_value_target']) ? (float)$targetData['booking_value_target'] : 0;
        
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
            $kpi['revenue_generation']['details'] = "Formula: (Actual Collection / Target Collection) × 30%<br>Calculation: (" . number_format($kpi['revenue_generation']['actual']) . " / " . number_format($kpi['revenue_generation']['target']) . ") × 30";
        } else {
            $kpi['revenue_generation']['details'] = "No target set for revenue generation.";
        }

        // ---------------------------------------------------------
        // 2. Target vs Achievement Value (20%)
        // Target: booking_value_target
        // Actual: Total value of flats booked in date range
        // ---------------------------------------------------------
        $kpi['target_vs_achievement']['target'] = isset($targetData['booking_value_target']) ? (float)$targetData['booking_value_target'] : 0;
        
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
            $kpi['target_vs_achievement']['details'] = "Formula: (Total Booked Value / Target Booking Value) × 20%<br>Calculation: (" . number_format($kpi['target_vs_achievement']['actual']) . " / " . number_format($kpi['target_vs_achievement']['target']) . ") × 20";
        } else {
            $kpi['target_vs_achievement']['details'] = "No target set for booking value.";
        }

        // ---------------------------------------------------------
        // 3. No. of Unit Sold (10%)
        // Target: booking_target
        // Actual: Count of booked units
        // ---------------------------------------------------------
        $kpi['unit_sold']['target'] = isset($targetData['booking_target']) ? (int)$targetData['booking_target'] : 0;
        
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
            $kpi['unit_sold']['details'] = "Formula: (Units Sold / Target Units) × 10%<br>Calculation: (" . $kpi['unit_sold']['actual'] . " / " . $kpi['unit_sold']['target'] . ") × 10";
        } else {
            $kpi['unit_sold']['details'] = "No unit target set.";
        }

        // ---------------------------------------------------------
        // 4. Highest Visit (Site Visit Ratio) (10%)
        // Target: site_visit_leads_target
        // Actual: Number of visits conducted
        // ---------------------------------------------------------
        $kpi['highest_visit']['target'] = isset($targetData['site_visit_leads_target']) ? (int)$targetData['site_visit_leads_target'] : 0;
        
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
            $kpi['highest_visit']['details'] = "Formula: (Actual Visits / Target Visits) × 10%<br>Calculation: (" . $kpi['highest_visit']['actual'] . " / " . $kpi['highest_visit']['target'] . ") × 10";
        } else {
            $kpi['highest_visit']['details'] = "No visit target set.";
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
        $kpi['sales_cvr']['details'] = "Formula: (Units Sold / Total Leads Assigned) × 100 = CVR%<br>Target: Min 1% CVR gives 10% score.<br>Actual CVR: " . $cvr . "%<br>Calculation: (" . $cvr . " / 1) × 10";

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
            $kpi['leads_connected']['details'] = "Formula: (Unique Leads Contacted / Total Leads Assigned) × 10%<br>Calculation: (" . $kpi['leads_connected']['actual'] . " / " . $totalAssigned . ") × 10";
        } else {
            $kpi['leads_connected']['score'] = 0; // 0% if no leads assigned
            $kpi['leads_connected']['details'] = "0% score awarded because no leads were assigned in this period.";
        }

        // Calculate ERP usage (distinct active days in CRM) early to use in Data Quality & HR
        $erpUsageQuery = "SELECT COUNT(DISTINCT DATE(created_at)) as active_days FROM lead_follow_ups WHERE created_by = " . intval($userId) . " AND created_at BETWEEN '$fromDate' AND '$toDate'";
        $erpUsageRes = $this->db->query($erpUsageQuery);
        $activeDays = 0;
        if ($erpUsageRes && $row = $erpUsageRes->fetch_assoc()) {
            $activeDays = (int)$row['active_days'];
        }

        // ---------------------------------------------------------
        // 7. Data Quality & Timeliness (5%)
        // ---------------------------------------------------------
        $kpi['data_quality']['target'] = 100; // 100% accurate
        if ($activeDays == 0 && $kpi['leads_connected']['actual'] == 0) {
            $kpi['data_quality']['actual'] = 0; 
            $kpi['data_quality']['score'] = 0;
            $kpi['data_quality']['details'] = "0% score awarded because there was no ERP activity or leads contacted.";
        } else {
            $kpi['data_quality']['actual'] = 100; 
            $kpi['data_quality']['score'] = 5;
            $kpi['data_quality']['details'] = "Measures proper data entry in CRM.<br>Status: Evaluated automatically (Currently 100%)";
        }

        // ---------------------------------------------------------
        // 8. HR (5%)
        // ERP Usage + Tipsoi API (Leave & Late)
        // ---------------------------------------------------------
        $leaveCount = isset($tipsoiStat['leave_count']) ? (int)$tipsoiStat['leave_count'] : 0;
        $lateCount = isset($tipsoiStat['late_count']) ? (int)$tipsoiStat['late_count'] : 0;
        
        // HR scoring logic (5% max):
        // If 0 active days in ERP, score is 0. 
        // Otherwise, Base 5%. Deduct 0.5% for each late, 1% for each leave.
        if ($activeDays == 0) {
            $hrScore = 0;
            $kpi['hr']['details'] = "<b>HR Calculation:</b><br>- ERP Usage (Active Days): 0<br>- Tipsoi Leave Count: {$leaveCount}<br>- Tipsoi Late Count: {$lateCount}<br><i>0% score awarded because there were 0 active days in ERP.</i>";
        } else {
            $deductions = ($lateCount * 0.5) + ($leaveCount * 1.0);
            $hrScore = max(0, 5 - $deductions);
            $kpi['hr']['details'] = "<b>HR Calculation:</b><br>- ERP Usage (Active Days): {$activeDays}<br>- Tipsoi Leave Count: {$leaveCount}<br>- Tipsoi Late Count: {$lateCount}<br><i>Formula: Base 5% minus (0.5% per Late + 1% per Leave) = " . number_format($hrScore, 2) . "%</i>";
        }

        $kpi['hr']['target'] = 100; 
        $kpi['hr']['actual'] = ($hrScore / 5) * 100; 
        $kpi['hr']['score'] = $hrScore;

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
