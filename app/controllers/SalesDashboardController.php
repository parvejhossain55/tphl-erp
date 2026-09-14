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

        $filterTeamId = isset($_GET['team_id']) && $_GET['team_id'] !== '' ? (int) $_GET['team_id'] : null;

        // Admin, Super Admin, Sales Head, Management, Chief Officer, SEAL CEO see all by default
        if (in_array($roleId, [1, 3, 9, 32, 33, 34, 35])) {
            $teamId = $filterTeamId;
        } else {
            // If they have no team_id, let them see everything (e.g. Sales Manager with no team)
            $teamId = !empty($sessionTeamId) ? (int) $sessionTeamId : $filterTeamId;
        }

        $followUpTeamId = !empty($teamId) ? $teamId : null;

        // Dashboard Date Filters
        $dashboardStartDate = $_GET['dashboard_start_date'] ?? '';
        $dashboardEndDate = $_GET['dashboard_end_date'] ?? '';
        $dateFilterType = $_GET['date_filter_type'] ?? 'custom';

        if ($dateFilterType === 'today') {
            $dashboardStartDate = date('Y-m-d');
            $dashboardEndDate = date('Y-m-d');
        }

        $leadDateCondition = "";
        $fuNextDateCondition = "";
        $fuCreatedCondition = "";
        $bookingDateCondition = "";
        $paymentDateCondition = "";

        if (!empty($dashboardStartDate) && !empty($dashboardEndDate)) {
            $leadDateCondition = " AND DATE(l.created_at) >= '$dashboardStartDate' AND DATE(l.created_at) <= '$dashboardEndDate' ";
            $fuNextDateCondition = " AND DATE(fu.next_follow_up_date) >= '$dashboardStartDate' AND DATE(fu.next_follow_up_date) <= '$dashboardEndDate' ";
            $fuCreatedCondition = " AND DATE(fu.created_at) >= '$dashboardStartDate' AND DATE(fu.created_at) <= '$dashboardEndDate' ";
            $bookingDateCondition = " AND DATE(lb.booking_date) >= '$dashboardStartDate' AND DATE(lb.booking_date) <= '$dashboardEndDate' ";
            $paymentDateCondition = " AND DATE(lp.payment_date) >= '$dashboardStartDate' AND DATE(lp.payment_date) <= '$dashboardEndDate' ";
        } elseif (!empty($dashboardStartDate)) {
            $leadDateCondition = " AND DATE(l.created_at) >= '$dashboardStartDate' ";
            $fuNextDateCondition = " AND DATE(fu.next_follow_up_date) >= '$dashboardStartDate' ";
            $fuCreatedCondition = " AND DATE(fu.created_at) >= '$dashboardStartDate' ";
            $bookingDateCondition = " AND DATE(lb.booking_date) >= '$dashboardStartDate' ";
            $paymentDateCondition = " AND DATE(lp.payment_date) >= '$dashboardStartDate' ";
        } elseif (!empty($dashboardEndDate)) {
            $leadDateCondition = " AND DATE(l.created_at) <= '$dashboardEndDate' ";
            $fuNextDateCondition = " AND DATE(fu.next_follow_up_date) <= '$dashboardEndDate' ";
            $fuCreatedCondition = " AND DATE(fu.created_at) <= '$dashboardEndDate' ";
            $bookingDateCondition = " AND DATE(lb.booking_date) <= '$dashboardEndDate' ";
            $paymentDateCondition = " AND DATE(lp.payment_date) <= '$dashboardEndDate' ";
        }

        //  Total Leads (All time)
        $totalLeadsCount = $this->leadModel->countAll(null, $teamId);

        //  New Leads (Filtered)
        $todayLeadsQuery = "SELECT COUNT(l.id) AS today_count FROM leads l LEFT JOIN employees emp ON emp.user_id = l.assigned_id WHERE l.deleted_at IS NULL AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated')) $leadDateCondition";
        if (!empty($teamId)) {
            $todayLeadsQuery .= " AND l.team_id = " . (int) $teamId;
        }
        $todayLeadsResult = $this->db->query($todayLeadsQuery);
        $todayLeads = $todayLeadsResult->fetch_assoc()['today_count'] ?? 0;

        //  Follow-ups Due (Filtered)
        $followUpsQuery = "
            SELECT COUNT(DISTINCT l.id) AS due 
            FROM leads l 
            JOIN lead_follow_ups fu ON fu.lead_id = l.id 
            LEFT JOIN employees emp ON emp.user_id = l.assigned_id 
            WHERE fu.deleted_at IS NULL 
            AND l.deleted_at IS NULL
            AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated')) 
            $leadDateCondition
        ";        if (!empty($teamId)) {
            $followUpsQuery .= " AND l.team_id = " . (int) $teamId;
        }
        $followUpsResult = $this->db->query($followUpsQuery);
        $followUpsDue = $followUpsResult->fetch_assoc()['due'] ?? 0;

        //  Onboarded Clients (Filtered)
        $onboardedQuery = "SELECT COUNT(*) AS onboarded FROM lead_bookings lb JOIN leads l ON lb.lead_id = l.id LEFT JOIN employees emp ON emp.user_id = l.assigned_id WHERE lb.booking_status IN ('completed', 'booked') AND lb.deleted_at IS NULL AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated')) $bookingDateCondition";
        if (!empty($teamId)) {
            $onboardedQuery .= " AND l.team_id = " . (int) $teamId;
        }
        $onboardedResult = $this->db->query($onboardedQuery);
        $onboardedClients = $onboardedResult->fetch_assoc()['onboarded'] ?? 0;

        //  Booking Money Collected (Filtered)
        $bookingCollectedQuery = "SELECT SUM(lp.amount) AS total_collected FROM lead_payments lp JOIN lead_bookings lb ON lp.booking_id = lb.id JOIN leads l ON lb.lead_id = l.id LEFT JOIN employees emp ON emp.user_id = l.assigned_id WHERE lp.payment_type='booking_money' AND lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated')) $paymentDateCondition";
        if (!empty($teamId)) {
            $bookingCollectedQuery .= " AND l.team_id = " . (int) $teamId;
        }
        $bookingCollectedResult = $this->db->query($bookingCollectedQuery);
        $bookingCollected = $bookingCollectedResult->fetch_assoc()['total_collected'] ?? 0;

        //  Conversion Rate
        $conversionRate = $totalLeadsCount > 0 ? ($onboardedClients / $totalLeadsCount) * 100 : 0;

        //  New Follow-ups Created (optional, filtered)
        $newFollowUpsQuery = "SELECT COUNT(*) AS new_followups FROM lead_follow_ups fu JOIN leads l ON fu.lead_id = l.id LEFT JOIN employees emp ON emp.user_id = l.assigned_id WHERE fu.deleted_at IS NULL AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated')) $fuCreatedCondition";
        if (!empty($teamId)) {
            $newFollowUpsQuery .= " AND l.team_id = " . (int) $teamId;
        }
        $newFollowUpsResult = $this->db->query($newFollowUpsQuery);
        $newFollowUpsToday = $newFollowUpsResult->fetch_assoc()['new_followups'] ?? 0;

        // Follow-up Count (Filtered by next_follow_up_date)
        $todayFollowUpSql = "SELECT COUNT(*) AS total FROM lead_follow_ups fu JOIN leads l ON fu.lead_id = l.id LEFT JOIN employees emp ON emp.user_id = l.assigned_id WHERE fu.deleted_at IS NULL AND fu.status = 'pending' AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated')) $fuNextDateCondition";
        if (!empty($followUpTeamId)) {
            $todayFollowUpSql .= " AND l.team_id = " . (int) $followUpTeamId;
        }
        $totalTodayFollowUps = $this->db->query($todayFollowUpSql)->fetch_assoc()['total'] ?? 0;

        // follow-ups table data
        $todayFollowUpData = $this->followUpModel->getTodaysFollowUps($followUpTeamId);

        // Total Contacted (from leads created in the date range)
        $contactedQuery = "
            SELECT COUNT(DISTINCT l.id) AS count 
            FROM leads l 
            LEFT JOIN employees emp ON emp.user_id = l.assigned_id 
            WHERE l.deleted_at IS NULL 
            AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated'))
            $leadDateCondition
            AND (
                l.last_lead_action IN ('contacted', 'follow up', 'booked', 'visited')
                OR EXISTS (
                    SELECT 1 FROM lead_follow_ups fu 
                    WHERE fu.lead_id = l.id AND fu.deleted_at IS NULL
                )
            )
        ";

        if (!empty($teamId)) {
            $contactedQuery .= " AND l.team_id = " . (int) $teamId;
        }
        $contactedCount = $this->db->query($contactedQuery)->fetch_assoc()['count'] ?? 0;
        $notContactedCount = max(0, $todayLeads - $contactedCount);

        // High Prospect Count (leads.lead_type = 'High Prospect')
        $highProspectQuery = "SELECT COUNT(l.id) AS count FROM leads l LEFT JOIN employees emp ON emp.user_id = l.assigned_id WHERE l.lead_type = 'High Prospect' AND l.deleted_at IS NULL AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated')) $leadDateCondition";
        if (!empty($teamId)) {
            $highProspectQuery .= " AND l.team_id = " . (int) $teamId;
        }
        $highProspectCount = $this->db->query($highProspectQuery)->fetch_assoc()['count'] ?? 0;

        // Will Visit Count (lead_follow_ups.next_action = 'site visit' AND fu.status = 'pending')
        $willVisitQuery = "SELECT COUNT(DISTINCT l.id) AS count FROM leads l JOIN lead_follow_ups fu ON fu.lead_id = l.id LEFT JOIN employees emp ON emp.user_id = l.assigned_id WHERE fu.next_action = 'site visit' AND fu.status = 'pending' AND fu.deleted_at IS NULL AND l.deleted_at IS NULL AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated')) $leadDateCondition";
        if (!empty($teamId)) {
            $willVisitQuery .= " AND l.team_id = " . (int) $teamId;
        }
        $willVisitCount = $this->db->query($willVisitQuery)->fetch_assoc()['count'] ?? 0;

        // Visited Count (leads.last_lead_action = 'visited' OR leads.lead_type = 'Visited')
        $visitedQuery = "SELECT COUNT(l.id) AS count FROM leads l LEFT JOIN employees emp ON emp.user_id = l.assigned_id WHERE (l.last_lead_action = 'visited' OR l.lead_type = 'Visited') AND l.deleted_at IS NULL AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated')) $leadDateCondition";
        if (!empty($teamId)) {
            $visitedQuery .= " AND l.team_id = " . (int) $teamId;
        }
        $visitedCount = $this->db->query($visitedQuery)->fetch_assoc()['count'] ?? 0;

        // Booked Count (leads.last_lead_action = 'booked')
        $bookedQuery = "SELECT COUNT(l.id) AS count FROM leads l LEFT JOIN employees emp ON emp.user_id = l.assigned_id WHERE l.last_lead_action = 'booked' AND l.deleted_at IS NULL AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated')) $leadDateCondition";
        if (!empty($teamId)) {
            $bookedQuery .= " AND l.team_id = " . (int) $teamId;
        }
        $bookedCount = $this->db->query($bookedQuery)->fetch_assoc()['count'] ?? 0;


        $weeklySalesQuery = "
            SELECT p.name AS project_name, COUNT(lb.id) AS total_sales
            FROM lead_bookings lb
            JOIN projects p ON p.id = lb.project_id
            JOIN leads l ON lb.lead_id = l.id
            WHERE lb.booking_status IN ('completed', 'booked') 
            AND lb.deleted_at IS NULL
            AND p.project_sale_status = 'On Sale'
            AND lb.booking_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ";
        if (!empty($teamId)) {
            $weeklySalesQuery .= " AND l.team_id = " . (int) $teamId;
        }
        $weeklySalesQuery .= " GROUP BY p.name ORDER BY total_sales DESC";
        $weeklySalesResult = $this->db->query($weeklySalesQuery);
        $weeklySales = [];
        while ($row = $weeklySalesResult->fetch_assoc()) {
            $weeklySales[] = [
                'date' => $row['project_name'],
                'total' => (int) $row['total_sales']
            ];
        }

        // MONTHLY / CUSTOM SALES
        $salesStartDate = $_GET['sales_start_date'] ?? null;
        $salesEndDate = $_GET['sales_end_date'] ?? null;

        $monthlySalesQuery = "
            SELECT p.name AS project_name, COUNT(lb.id) AS total_sales
            FROM lead_bookings lb
            JOIN projects p ON p.id = lb.project_id
            JOIN leads l ON lb.lead_id = l.id
            WHERE lb.booking_status IN ('completed', 'booked') 
            AND lb.deleted_at IS NULL
            AND p.project_sale_status = 'On Sale'
        ";
        
        if ($salesStartDate && $salesEndDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $salesStartDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $salesEndDate)) {
            $monthlySalesQuery .= " AND DATE(lb.booking_date) >= '" . $salesStartDate . "' AND DATE(lb.booking_date) <= '" . $salesEndDate . "'";
        } else {
            $monthlySalesQuery .= " AND lb.booking_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        }
        if (!empty($teamId)) {
            $monthlySalesQuery .= " AND l.team_id = " . (int) $teamId;
        }
        $monthlySalesQuery .= " GROUP BY p.name ORDER BY total_sales DESC";
        $monthlySalesResult = $this->db->query($monthlySalesQuery);
        $monthlySales = [];
        while ($row = $monthlySalesResult->fetch_assoc()) {
            $monthlySales[] = [
                'date' => $row['project_name'],
                'total' => (int) $row['total_sales']
            ];
        }

        // YEARLY SALES (Last 12 months)
        $yearlySalesQuery = "
            SELECT p.name AS project_name, COUNT(lb.id) AS total_sales
            FROM lead_bookings lb
            JOIN projects p ON p.id = lb.project_id
            JOIN leads l ON lb.lead_id = l.id
            WHERE lb.booking_status IN ('completed', 'booked') 
            AND lb.deleted_at IS NULL
            AND p.project_sale_status = 'On Sale'
            AND lb.booking_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
        ";
        if (!empty($teamId)) {
            $yearlySalesQuery .= " AND l.team_id = " . (int) $teamId;
        }
        $yearlySalesQuery .= " GROUP BY p.name ORDER BY total_sales DESC";
        $yearlySalesResult = $this->db->query($yearlySalesQuery);
        $yearlySales = [];
        while ($row = $yearlySalesResult->fetch_assoc()) {
            $yearlySales[] = [
                'month' => $row['project_name'],
                'total' => (int) $row['total_sales']
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
            $flatSalesQuery .= " AND l.team_id = " . (int) $teamId;
        }
        $flatSalesQuery .= " GROUP BY lb.project_id ORDER BY flat_sold DESC";

        $result = $this->db->query($flatSalesQuery);

        $flatSalesLabels = [];
        $flatSalesSeries = [];

        while ($row = $result->fetch_assoc()) {
            $flatSalesLabels[] = $row['project_name'];
            $flatSalesSeries[] = (int) $row['flat_sold'];
        }


        $totalSalesTeams = $this->userModel->countSalesTeams($roleId, $teamId);

        $weeklyData = $this->salesTargetModel->getExecutiveBookingStats('weekly');
        $monthlyData = $this->salesTargetModel->getExecutiveBookingStats('monthly');
        $yearlyData = $this->salesTargetModel->getExecutiveBookingStats('yearly');
        $fullData = $this->salesTargetModel->getExecutiveBookingStats('fullData');
        // $userTeamId = $_SESSION['user']['team_id'] ?? null;

        // Team Booking Performance Filter
        $teamFilterType = $_GET['team_filter_type'] ?? 'monthly';
        $teamYear = isset($_GET['team_year']) ? (int) $_GET['team_year'] : (int) date('Y');
        $teamStartMonth = isset($_GET['team_start_month']) ? (int) $_GET['team_start_month'] : (int) date('n');
        $teamEndMonth = isset($_GET['team_end_month']) ? (int) $_GET['team_end_month'] : (int) date('n');
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
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        $singleData = [
            'site_visit_target' => 0,
            'booking_target' => 0,
            'booking_value_target' => 0,
            'visit_completed' => 0,
            'booking_completed' => 0,
            'total_booking_money' => 0,
            'total_flat_value' => 0,
        ];

        $showCards = false;
        $errorMessage = '';

        if (!empty($executiveId)) {
            if (empty($startDate) || empty($endDate)) {
                // Employee selected but no date range
                $errorMessage = "Please select Start Date and End Date!";
            } else {
                // Employee + Date range selected → fetch data
                $data = $this->salesTargetModel->getExecutiveFullStats($executiveId, $startDate, $endDate);

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
            "errorMessage" => $errorMessage,
            "executiveId" => $executiveId,
            "start_date" => $startDate,
            "end_date" => $endDate,
            "teamFilterType" => $teamFilterType,
            "teamYear" => $teamYear,
            "teamStartMonth" => $teamStartMonth,
            "teamEndMonth" => $teamEndMonth,
            "defaultTeamTab" => $defaultTeamTab,


            "contactedCount" => $contactedCount,
            "highProspectCount" => $highProspectCount,
            "willVisitCount" => $willVisitCount,
            "visitedCount" => $visitedCount,
            "bookedCount" => $bookedCount,
            "notContactedCount" => $notContactedCount,
            "dashboardStartDate" => $dashboardStartDate,
            "dashboardEndDate" => $dashboardEndDate,
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
            "page_title" => "Sales Incentive Target",
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
        $projects = $this->db->query("
            SELECT p.id, p.name, p.category_id, pc.name as cat_name, pc.main_category 
            FROM projects p 
            LEFT JOIN project_categories pc ON p.category_id = pc.id 
            WHERE p.deleted_at IS NULL 
            ORDER BY p.name ASC
        ")->fetch_all(MYSQLI_ASSOC);
        $team = $this->db->query("SELECT t.name FROM users u JOIN teams t ON u.team_id = t.id WHERE u.id = $id LIMIT 1")->fetch_assoc();
        $teamName = $team['name'] ?? 'No Team';
        
        $categories = $this->db->query("SELECT id, name AS category_name, main_category FROM project_categories ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view("sales-developments/set-sales-target", [
            "current_route" => "sales-executive-teams",
            'user' => $user,
            'targets' => $targets,
            'projects' => $projects,
            'categories' => $categories,
            'teamName' => $teamName
        ]);
    }

    // Store / update target
    public function storeSalesTarget($id)
    {
        $monthInput = $_POST['month']; // e.g. 2026-02
        $month = date('Y-m-01', strtotime($monthInput));

        $categoryIds = !empty($_POST['category_ids']) ? (array)$_POST['category_ids'] : [];
        $categoryId = !empty($categoryIds) ? (int)reset($categoryIds) : (!empty($_POST['category_id']) ? (int)$_POST['category_id'] : null);
        $categoryIdsJson = !empty($categoryIds) ? json_encode(array_values(array_map('strval', $categoryIds))) : null;

        $projectIds = !empty($_POST['project_ids']) ? json_encode(array_values(array_map('strval', (array)$_POST['project_ids']))) : null;
        $siteVisit = (int)($_POST['site_visit_target'] ?? 0);
        $bookingTarget = (int)($_POST['booking_target'] ?? 0);
        $bookingValue = (float)($_POST['booking_value_target'] ?? 0);
        $salesRole = $_POST['sales_role'] ?? null;
        
        if (empty($salesRole)) {
            $_SESSION['error'] = "Sales Role is required!";
            header("Location: " . $GLOBALS['base_url'] . "/set-sales-target/" . $id);
            exit;
        }

        $saved = $this->salesTargetModel->saveTarget(
            $id,
            $month,
            $siteVisit,
            $bookingTarget,
            $bookingValue,
            $categoryId,
            $projectIds,
            $salesRole,
            $categoryIdsJson
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
        $id = (int)$_POST['id']; // hidden input
        $monthInput = $_POST['month'];
        $month = date('Y-m-01', strtotime($monthInput));

        $categoryIds = !empty($_POST['category_ids']) ? (array)$_POST['category_ids'] : [];
        $categoryId = !empty($categoryIds) ? (int)reset($categoryIds) : (!empty($_POST['category_id']) ? (int)$_POST['category_id'] : null);
        $categoryIdsJson = !empty($categoryIds) ? json_encode(array_values(array_map('strval', $categoryIds))) : null;

        $projectIds = !empty($_POST['project_ids']) ? json_encode(array_values(array_map('strval', (array)$_POST['project_ids']))) : null;

        $siteVisit = (int)($_POST['site_visit_target'] ?? 0);
        $booking = (int)($_POST['booking_target'] ?? 0);
        $bookingValue = (float)($_POST['booking_value_target'] ?? 0);

        $salesRole = $_POST['sales_role'] ?? null;
        $userId = (int)$_POST['user_id'];
        
        if (empty($salesRole)) {
            $_SESSION['error'] = "Sales Role is required!";
            header("Location: " . $GLOBALS['base_url'] . "/set-sales-target/" . $userId);
            exit;
        }

        $sql = "
            UPDATE sales_targets 
            SET month = ?, 
                site_visit_target = ?, 
                booking_target = ?, 
                booking_value_target = ?,
                category_id = ?,
                category_ids = ?,
                project_ids = ?,
                sales_role = ?
            WHERE id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("siidisssi", $month, $siteVisit, $booking, $bookingValue, $categoryId, $categoryIdsJson, $projectIds, $salesRole, $id);
        $stmt->execute();

        $_SESSION['success'] = "Target updated successfully!";
        header("Location: " . $GLOBALS['base_url'] . "/set-sales-target/" . $userId);
        exit;
    }
}
