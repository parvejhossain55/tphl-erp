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
        //  Total Leads
        $totalLeads = $this->leadModel->getAll();
        $totalLeadsCount = count($totalLeads);

        //  New Leads Today
        $todayLeadsQuery = "SELECT COUNT(*) AS today_count FROM leads WHERE DATE(created_at) = CURDATE()";
        $todayLeadsResult = $this->db->query($todayLeadsQuery);
        $todayLeads = $todayLeadsResult->fetch_assoc()['today_count'] ?? 0;

        //  Follow-ups Due
        $followUpsQuery = "SELECT COUNT(*) AS due FROM lead_follow_ups WHERE follow_up_date <= CURDATE() AND status = 'pending' AND lead_follow_ups.deleted_at IS NULL";
        $followUpsResult = $this->db->query($followUpsQuery);
        $followUpsDue = $todayLeads;

        //  Onboarded Clients
        $onboardedQuery = "SELECT COUNT(*) AS onboarded FROM lead_bookings WHERE booking_status = 'completed' AND lead_bookings.deleted_at IS NULL";
        $onboardedResult = $this->db->query($onboardedQuery);
        $onboardedClients = $onboardedResult->fetch_assoc()['onboarded'] ?? 0;

        //  Booking Money Collected
        $bookingCollectedQuery = "SELECT SUM(amount) AS total_collected FROM lead_payments WHERE payment_type='booking_money' AND payment_status='paid'";
        $bookingCollectedResult = $this->db->query($bookingCollectedQuery);
        $bookingCollected = $bookingCollectedResult->fetch_assoc()['total_collected'] ?? 0;

        //  Conversion Rate
        $conversionRate = $totalLeadsCount > 0 ? ($onboardedClients / $totalLeadsCount) * 100 : 0;

        //  New Follow-ups Today (optional)
        $newFollowUpsQuery = "SELECT COUNT(*) AS new_followups FROM lead_follow_ups WHERE DATE(created_at) = CURDATE() AND lead_follow_ups.deleted_at IS NULL";
        $newFollowUpsResult = $this->db->query($newFollowUpsQuery);
        $newFollowUpsToday = $newFollowUpsResult->fetch_assoc()['new_followups'] ?? 0;

        $todayFollowUpCount = $this->followUpModel->getTodayFollowUpCountByLead();
        $totalTodayFollowUps = array_sum($todayFollowUpCount);

        // today's follow-ups table data
        $todayFollowUpData = $this->followUpModel->getTodaysFollowUps();


        $weeklySalesQuery = "
            SELECT DATE(payment_date) AS date, SUM(amount) AS total
            FROM lead_payments
            WHERE payment_status='paid' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(payment_date)
            ORDER BY DATE(payment_date)
        ";
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
        SELECT DATE(payment_date) AS date, SUM(amount) AS total
        FROM lead_payments
        WHERE payment_status='paid' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(payment_date)
        ORDER BY DATE(payment_date)";
        $monthlySalesResult = $this->db->query($monthlySalesQuery);
        $monthlySales = [];
        while ($row = $monthlySalesResult->fetch_assoc()) {
            $monthlySales[] = [
                'date' => $row['date'],
                'total' => (float)$row['total']
            ];
        }

        // YEARLY SALES (Last 12 months)
        $yearlySalesQuery = "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS month, SUM(amount) AS total
        FROM lead_payments
        WHERE payment_status='paid' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
        GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
        ORDER BY DATE_FORMAT(payment_date, '%Y-%m')";
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
                            WHERE lb.booking_status = 'completed'
                            AND lb.deleted_at IS NULL
                            GROUP BY lb.project_id
                            ORDER BY flat_sold DESC
                        ";

        $result = $this->db->query($flatSalesQuery);

        $flatSalesLabels = [];
        $flatSalesSeries = [];

        while ($row = $result->fetch_assoc()) {
            $flatSalesLabels[] = $row['project_name'];
            $flatSalesSeries[] = (int)$row['flat_sold'];
        }


        $totalSalesTeams = $this->userModel->countSalesTeams();

        $weeklyData = $this->salesTargetModel->getExecutiveBookingStats('weekly');
        $monthlyData = $this->salesTargetModel->getExecutiveBookingStats('monthly');
        $yearlyData = $this->salesTargetModel->getExecutiveBookingStats('yearly');
        $fullData = $this->salesTargetModel->getExecutiveBookingStats('fullData');

        $allSalesTeams = $this->userModel->getSalesTeams();
        $executiveId = $_GET['executive'] ?? null;
        $month       = $_GET['month'] ?? null;
        $year        = $_GET['year'] ?? null;

        $singleData = [
            'site_visit_target'   => 0,
            'booking_target'      => 0,
            'booking_value_target'=> 0,
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
                $data = $this->salesTargetModel->getExecutiveFullStats($executiveId, $month, $year);

                if (!empty($data)) {
                    $singleData = $data[0];
                }

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
            "month"  => $month,
        ]);
    }

    public function salesTeam()
    {
        $searchTerm = $_GET['q'] ?? '';
        $roleFilter = $_GET['role'] ?? '';

        $currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $perPage = 10;
        $offset = ($currentPage - 1) * $perPage;

        // Total user count with filters
        $totalUsers = $this->userModel->countAllForSalesExecutive($searchTerm);
        $totalPages = ceil($totalUsers / $perPage);

        // Get paginated users with search & role filter
        $salesUsers = $this->userModel->getPaginatedWithSalesExecutive($perPage, $offset, $searchTerm, $roleFilter);

        // Roles for filter dropdown
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
