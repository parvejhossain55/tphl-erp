<?php

class SalesDashboardController extends Controller
{
    protected $db;
    protected $leadModel;
    protected $followUpModel;
    protected $projectModel;
    protected $bookingModel;
    protected $paymentModel;
    protected $userModel;
    protected $salesTargetModel;
    protected $roleModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->leadModel = new Lead($this->db);
        $this->followUpModel = new FollowUp($this->db);
        $this->bookingModel = new BookingLead($this->db);
        $this->paymentModel = new PaymentLeadBooking($this->db);
        $this->projectModel = new Project($this->db);
        $this->userModel = new User($this->db);
        $this->salesTargetModel = new SalesTarget($this->db);
        $this->roleModel = new Role($this->db);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index()
    {
        
        $roleId = $_SESSION['user']['role_id'] ?? null;
        $sessionTeamId = $_SESSION['user']['team_id'] ?? null;
        
        $teamId = null;
        if ($roleId == 5 && !empty($sessionTeamId)) {
            $teamId = $sessionTeamId;
        }

        $followUpTeamId = null;
        if (in_array($roleId, [5, 9]) && !empty($sessionTeamId)) {
            $followUpTeamId = $sessionTeamId;
        }

        //  Total Leads
        $totalLeads = $this->leadModel->getAll(null, $roleId, $teamId);
        $totalLeadsCount = count($totalLeads);

        //  New Leads Today
        $todayLeadsQuery = "SELECT COUNT(l.id) AS today_count FROM leads l LEFT JOIN employees emp ON emp.user_id = l.assigned_id WHERE DATE(l.created_at) = CURDATE() AND l.deleted_at IS NULL AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated'))";
        if (!empty($teamId)) {
            $todayLeadsQuery .= " AND l.team_id = " . (int)$teamId;
        }
        $todayLeadsResult = $this->db->query($todayLeadsQuery);
        $todayLeads = $todayLeadsResult->fetch_assoc()['today_count'] ?? 0;

        //  Follow-ups Due
        $followUpsQuery = "SELECT COUNT(*) AS due FROM lead_follow_ups fu JOIN leads l ON fu.lead_id = l.id LEFT JOIN employees emp ON emp.user_id = l.assigned_id WHERE fu.next_follow_up_date <= CURDATE() AND fu.status = 'pending' AND fu.deleted_at IS NULL AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated'))";
        if (!empty($teamId)) {
            $followUpsQuery .= " AND l.team_id = " . (int)$teamId;
        }
        $followUpsResult = $this->db->query($followUpsQuery);
        $followUpsDue = $followUpsResult->fetch_assoc()['due'] ?? 0;

        //  Onboarded Clients
        $onboardedQuery = "SELECT COUNT(*) AS onboarded FROM lead_bookings lb JOIN leads l ON lb.lead_id = l.id LEFT JOIN employees emp ON emp.user_id = l.assigned_id WHERE lb.booking_status IN ('completed', 'booked') AND lb.deleted_at IS NULL AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated'))";
        if (!empty($teamId)) {
            $onboardedQuery .= " AND l.team_id = " . (int)$teamId;
        }
        $onboardedResult = $this->db->query($onboardedQuery);
        $onboardedClients = $onboardedResult->fetch_assoc()['onboarded'] ?? 0;

        //  Booking Money Collected
        $bookingCollectedQuery = "SELECT SUM(lp.amount) AS total_collected FROM lead_payments lp JOIN lead_bookings lb ON lp.booking_id = lb.id JOIN leads l ON lb.lead_id = l.id LEFT JOIN employees emp ON emp.user_id = l.assigned_id WHERE lp.payment_type='booking_money' AND lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated'))";
        if (!empty($teamId)) {
            $bookingCollectedQuery .= " AND l.team_id = " . (int)$teamId;
        }
        $bookingCollectedResult = $this->db->query($bookingCollectedQuery);
        $bookingCollected = $bookingCollectedResult->fetch_assoc()['total_collected'] ?? 0;

        //  Conversion Rate
        $conversionRate = $totalLeadsCount > 0 ? ($onboardedClients / $totalLeadsCount) * 100 : 0;

        //  New Follow-ups Today (optional)
        $newFollowUpsQuery = "SELECT COUNT(*) AS new_followups FROM lead_follow_ups fu JOIN leads l ON fu.lead_id = l.id LEFT JOIN employees emp ON emp.user_id = l.assigned_id WHERE DATE(fu.created_at) = CURDATE() AND fu.deleted_at IS NULL AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated'))";
        if (!empty($teamId)) {
            $newFollowUpsQuery .= " AND l.team_id = " . (int)$teamId;
        }
        $newFollowUpsResult = $this->db->query($newFollowUpsQuery);
        $newFollowUpsToday = $newFollowUpsResult->fetch_assoc()['new_followups'] ?? 0;

        // Today's Follow-up Count (Filtered by team)
        $todayFollowUpSql = "SELECT COUNT(*) AS total FROM lead_follow_ups fu JOIN leads l ON fu.lead_id = l.id LEFT JOIN employees emp ON emp.user_id = l.assigned_id WHERE DATE(fu.next_follow_up_date) = CURDATE() AND fu.deleted_at IS NULL AND fu.status = 'pending' AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated'))";
        if (!empty($followUpTeamId)) {
            $todayFollowUpSql .= " AND l.team_id = " . (int)$followUpTeamId;
        }
        $totalTodayFollowUps = $this->db->query($todayFollowUpSql)->fetch_assoc()['total'] ?? 0;

        // today's follow-ups table data
        // We'll update the model method or use custom logic if needed, but for now let's keep the existing call if it's acceptable, or add a team filter to it.
        $todayFollowUpData = $this->followUpModel->getTodaysFollowUps($followUpTeamId);


        $weeklySalesQuery = "
            SELECT DATE(lp.payment_date) AS date, SUM(lp.amount) AS total
            FROM lead_payments lp
            JOIN lead_bookings lb ON lp.booking_id = lb.id
            JOIN leads l ON lb.lead_id = l.id
            WHERE lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' AND lp.payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ";
        if (!empty($teamId)) {
            $weeklySalesQuery .= " AND l.team_id = " . (int)$teamId;
        }
        $weeklySalesQuery .= " GROUP BY DATE(lp.payment_date) ORDER BY DATE(lp.payment_date)";
        $weeklySalesResult = $this->db->query($weeklySalesQuery);
        $weeklySales = [];
        while ($row = $weeklySalesResult->fetch_assoc()) {
            $weeklySales[] = [
                'date' => $row['date'],
                'total' => (float)$row['total']
            ];
        }

        // MONTHLY SALES (Last 30 days)
        $monthlySalesQuery = "
            SELECT DATE(lp.payment_date) AS date, SUM(lp.amount) AS total
            FROM lead_payments lp
            JOIN lead_bookings lb ON lp.booking_id = lb.id
            JOIN leads l ON lb.lead_id = l.id
            WHERE lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' AND lp.payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ";
        if (!empty($teamId)) {
            $monthlySalesQuery .= " AND l.team_id = " . (int)$teamId;
        }
        $monthlySalesQuery .= " GROUP BY DATE(lp.payment_date) ORDER BY DATE(lp.payment_date)";
        $monthlySalesResult = $this->db->query($monthlySalesQuery);
        $monthlySales = [];
        while ($row = $monthlySalesResult->fetch_assoc()) {
            $monthlySales[] = [
                'date' => $row['date'],
                'total' => (float)$row['total']
            ];
        }

        // YEARLY SALES (Last 12 months)
        $yearlySalesQuery = "
            SELECT DATE_FORMAT(lp.payment_date, '%Y-%m') AS month, SUM(lp.amount) AS total
            FROM lead_payments lp
            JOIN lead_bookings lb ON lp.booking_id = lb.id
            JOIN leads l ON lb.lead_id = l.id
            WHERE lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' AND lp.payment_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
        ";
        if (!empty($teamId)) {
            $yearlySalesQuery .= " AND l.team_id = " . (int)$teamId;
        }
        $yearlySalesQuery .= " GROUP BY DATE_FORMAT(lp.payment_date, '%Y-%m') ORDER BY DATE_FORMAT(lp.payment_date, '%Y-%m')";
        $yearlySalesResult = $this->db->query($yearlySalesQuery);
        $yearlySales = [];
        while ($row = $yearlySalesResult->fetch_assoc()) {
            $yearlySales[] = [
                'month' => $row['month'],
                'total' => (float)$row['total']
            ];
        }

        // project wise flat sales data
        $totalProjects = $this->projectModel->countProject();




        $flatSalesQuery = "SELECT p.name AS project_name, COUNT(lb.id) AS flat_sold
                            FROM lead_bookings lb
                            JOIN projects p ON p.id = lb.project_id
                            JOIN leads l ON lb.lead_id = l.id
                            WHERE lb.booking_status IN ('completed', 'booked')
                            AND lb.deleted_at IS NULL
                        ";
        if (!empty($teamId)) {
            $flatSalesQuery .= " AND l.team_id = " . (int)$teamId;
        }
        $flatSalesQuery .= " GROUP BY lb.project_id ORDER BY flat_sold DESC";

        $result = $this->db->query($flatSalesQuery);

        $flatSalesLabels = [];
        $flatSalesSeries = [];

        while ($row = $result->fetch_assoc()) {
            $flatSalesLabels[] = $row['project_name'];
            $flatSalesSeries[] = (int)$row['flat_sold'];
        }


        $totalSalesTeams = $this->userModel->countSalesTeams($roleId, $teamId);

        $weeklyData = $this->salesTargetModel->getExecutiveBookingStats('weekly');
        $monthlyData = $this->salesTargetModel->getExecutiveBookingStats('monthly');
        $yearlyData = $this->salesTargetModel->getExecutiveBookingStats('yearly');
        $fullData = $this->salesTargetModel->getExecutiveBookingStats('fullData');
        // $userTeamId = $_SESSION['user']['team_id'] ?? null;

        // Team Booking Performance Filter
        $teamFilterType = $_GET['team_filter_type'] ?? 'monthly';
        $teamYear       = isset($_GET['team_year']) ? (int)$_GET['team_year'] : (int)date('Y');
        $teamStartMonth = isset($_GET['team_start_month']) ? (int)$_GET['team_start_month'] : (int)date('n');
        $teamEndMonth   = isset($_GET['team_end_month']) ? (int)$_GET['team_end_month'] : (int)date('n');
        $defaultTeamTab = 'month1';

        if (isset($_GET['team_filter_type'])) {
            if ($teamFilterType === 'yearly') {
                $yearlyData = $this->salesTargetModel->getSalesTeamsBookingStatsByRange($teamYear, 1, 12);
                $defaultTeamTab = 'year1';
            } else {
                $monthlyData = $this->salesTargetModel->getSalesTeamsBookingStatsByRange($teamYear, $teamStartMonth, $teamEndMonth);
                $defaultTeamTab = 'month1';
            }
        }

        $allSalesTeams = $this->userModel->getSalesTeams($roleId, $teamId);
        $executiveId = $_GET['executive'] ?? null;
        $startMonth  = $_GET['start_month'] ?? null;
        $endMonth    = $_GET['end_month'] ?? null;
        $year        = $_GET['year'] ?? null;

        $singleData = [
            'site_visit_target'   => 0,
            'booking_target'      => 0,
            'booking_value_target' => 0,
            'visit_completed'     => 0,
            'booking_completed'   => 0,
            'total_booking_money' => 0,
            'total_flat_value'    => 0,
        ];

        $showCards = false;
        $errorMessage = '';

        if (!empty($executiveId)) {
            if (empty($year)) {
                // Employee selected but no year
                $errorMessage = "Please select a Year first!";
            } else {
                // Employee + Year selected → fetch data
                $data = $this->salesTargetModel->getExecutiveFullStats($executiveId, $startMonth, $endMonth, $year);

                if (!empty($data)) {
                    $singleData = $data[0];
                }

                // 1. Total Leads Assigned
                $totalLeadsExecQuery = "SELECT COUNT(*) AS count FROM leads WHERE assigned_id = $executiveId AND deleted_at IS NULL";
                $totalLeadsExecResult = $this->db->query($totalLeadsExecQuery);
                $singleData['total_leads_assigned'] = $totalLeadsExecResult->fetch_assoc()['count'] ?? 0;

                // 2. Today Total Leads Assigned
                $todayLeadsExecQuery = "
                    SELECT COUNT(*) AS count 
                    FROM leads 
                    WHERE assigned_id = $executiveId 
                    AND deleted_at IS NULL 
                    AND (
                        (created_at >= CURDATE() AND created_at < CURDATE() + INTERVAL 1 DAY)
                        OR (assigned_at IS NOT NULL AND assigned_at >= CURDATE() AND assigned_at < CURDATE() + INTERVAL 1 DAY)
                    )
                ";
                $todayLeadsExecResult = $this->db->query($todayLeadsExecQuery);
                $singleData['today_leads_assigned'] = $todayLeadsExecResult->fetch_assoc()['count'] ?? 0;

                // 3. Today Total Contacted
                $todayContactedExecQuery = "
                    SELECT COUNT(*) AS count 
                    FROM leads 
                    WHERE assigned_id = $executiveId 
                    AND deleted_at IS NULL 
                    AND call_status != 'not_called'
                    AND (
                        (created_at >= CURDATE() AND created_at < CURDATE() + INTERVAL 1 DAY)
                        OR (assigned_at IS NOT NULL AND assigned_at >= CURDATE() AND assigned_at < CURDATE() + INTERVAL 1 DAY)
                    )
                ";
                $todayContactedExecResult = $this->db->query($todayContactedExecQuery);
                $singleData['today_contacted'] = $todayContactedExecResult->fetch_assoc()['count'] ?? 0;

                // 4. Today Remaining Data
                $singleData['today_remaining'] = max(0, $singleData['today_leads_assigned'] - $singleData['today_contacted']);

                // 5. Total Follow up Data Yet
                $totalFollowUpQuery = "SELECT COUNT(*) AS count FROM lead_follow_ups WHERE assigned_with = $executiveId AND deleted_at IS NULL";
                $totalFollowUpResult = $this->db->query($totalFollowUpQuery);
                $singleData['total_follow_ups_yet'] = $totalFollowUpResult->fetch_assoc()['count'] ?? 0;

                // 6. Today Follow up
                $todayFollowUpQuery = "SELECT COUNT(*) AS count FROM lead_follow_ups WHERE assigned_with = $executiveId AND deleted_at IS NULL AND DATE(next_follow_up_date) = CURDATE()";
                $todayFollowUpResult = $this->db->query($todayFollowUpQuery);
                $singleData['today_follow_ups'] = $todayFollowUpResult->fetch_assoc()['count'] ?? 0;

                $showCards = true;
            }
        }




        // Pass variables to view
        $this->view("sales-developments/sales-dashboard", [
            "current_route" => "sales-dashboard",
            "page_title" => "Sales Dashboard",
            "totalLeadsCount" => $totalLeadsCount,
            "todayLeads" => $todayLeads,
            "followUpsDue" => $followUpsDue,
            "onboardedClients" => $onboardedClients,
            "bookingCollected" => $bookingCollected,
            "conversionRate" => $conversionRate,
            "newFollowUpsToday" => $newFollowUpsToday,
            "weeklySales" => $weeklySales,
            "monthlySales" => $monthlySales,
            "yearlySales" => $yearlySales,
            "totalProjects" => $totalProjects,
            "todayFollowUpData" => $todayFollowUpData,
            "totalTodayFollowUps" => $totalTodayFollowUps,
            "flatSalesLabels" => $flatSalesLabels,
            "flatSalesSeries" => $flatSalesSeries,
            "weeklyData" => $weeklyData,
            "monthlyData" => $monthlyData,
            "yearlyData" => $yearlyData,
            "fullData" => $fullData,
            "totalSalesTeams" => $totalSalesTeams,
            "allSalesTeams" => $allSalesTeams,
            "singleData" => $singleData,
            "showCards" => $showCards,
            "errorMessage"  => $errorMessage,
            "executiveId"  => $executiveId,
            "year"  => $year,
            "start_month"  => $startMonth,
            "end_month"    => $endMonth,
            "teamFilterType" => $teamFilterType,
            "teamYear"       => $teamYear,
            "teamStartMonth" => $teamStartMonth,
            "teamEndMonth"   => $teamEndMonth,
            "defaultTeamTab" => $defaultTeamTab,
        ]);
    }

    public function salesTeam()
    {
        $searchTerm = $_GET['q'] ?? '';

        $currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $perPage = 10;
        $offset = ($currentPage - 1) * $perPage;

        $roleId = $_SESSION['user']['role_id'] ?? null;
        $teamId = $_SESSION['user']['team_id'] ?? null;

        // count
        $totalUsers = $this->userModel->countAllForSalesExecutive($searchTerm, $roleId, $teamId);
        $totalPages = ceil($totalUsers / $perPage);

        // data
        $salesUsers = $this->userModel->getPaginatedWithSalesExecutive(
            $perPage,
            $offset,
            $searchTerm,
            $roleId,
            $teamId
        );

        $roles = $this->roleModel->getAll();

        $this->view('sales-developments/sales-executive-teams', [
            "current_route" => "sales-executive-teams",
            "page_title" => "Sales Executive Team",
            'salesUsers' => $salesUsers,
            'roles' => $roles,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }

    public function salesTarget($id)
    {
        $user = $this->userModel->find($id);
        $targets = $this->salesTargetModel->getAllTargets($id);

        $this->view("sales-developments/set-sales-target", [
            "current_route" => "sales-executive-teams",
            'user' => $user,
            'targets' => $targets
        ]);
    }

    // Store / update target
    public function storeSalesTarget($id)
    {
        $monthInput = $_POST['month']; // e.g. 2026-02
        $month = date('Y-m-01', strtotime($monthInput));

        $siteVisit = $_POST['site_visit_target'] ?? 0;
        $bookingTarget = $_POST['booking_target'] ?? 0;
        $bookingValue = $_POST['booking_value_target'] ?? 0;

        $saved = $this->salesTargetModel->saveTarget(
            $id,
            $month,
            $siteVisit,
            $bookingTarget,
            $bookingValue
        );

        if ($saved) {
            $_SESSION['success'] = "Target saved successfully!";
        } else {
            $_SESSION['error'] = "Failed to save target!";
        }

        header("Location: " . $GLOBALS['base_url'] . "/set-sales-target/" . $id);
        exit;
    }

    public function updateSalesTarget()
    {
        $id = $_POST['id']; // hidden input
        $monthInput = $_POST['month'];
        $month = date('Y-m-01', strtotime($monthInput));
        $siteVisit = $_POST['site_visit_target'] ?? 0;
        $booking = $_POST['booking_target'] ?? 0;
        $bookingValue = $_POST['booking_value_target'] ?? 0;

        $sql = "
        UPDATE sales_targets 
        SET month = ?, 
            site_visit_target = ?, 
            booking_target = ?, 
            booking_value_target = ?
        WHERE id = ?
    ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("siiii", $month, $siteVisit, $booking, $bookingValue, $id);
        $stmt->execute();

        $_SESSION['success'] = "Target updated successfully!";
        header("Location: " . $GLOBALS['base_url'] . "/set-sales-target/" . $_POST['user_id']);
        exit;
    }
}
