<?php

class SalesTeamKpiController extends Controller
{
    protected $db;
    protected $leadModel;
    protected $followUpModel;
    protected $projectModel;
    protected $bookingModel;
    protected $paymentModel;
    protected $userModel;
    protected $designationModel;
    protected $roleModel;
    public function __construct($db)
    {


        $this->db = $db;
        $this->leadModel = new Lead($this->db);
        $this->followUpModel = new FollowUp($this->db);
        $this->bookingModel = new BookingLead($this->db);
        $this->paymentModel = new PaymentLeadBooking($this->db);
        $this->projectModel = new Project($this->db);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->designationModel = new Designation($this->db);
        $this->userModel = new User($this->db);
        $this->roleModel = new Role($this->db);
    }

    public function index()
    {
        $currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $perPage = 8;

        $filters = [
            'search' => trim($_GET['q'] ?? ''),
            'executive' => !empty($_GET['executive']) ? (int) $_GET['executive'] : null,
            'year' => !empty($_GET['year']) ? (int) $_GET['year'] : null,
            'month' => !empty($_GET['month']) ? (int) $_GET['month'] : null,
            'has_bookings' => isset($_GET['has_bookings']),
            'has_visits' => isset($_GET['has_visits']),
        ];

        $allKpiData = $this->leadModel->getSalesTeamKpiData($filters);
        $totalItems = count($allKpiData);
        $totalPages = $totalItems > 0 ? ceil($totalItems / $perPage) : 1;

        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }

        $kpiData = array_slice($allKpiData, ($currentPage - 1) * $perPage, $perPage);

        $summary = [
            'total_leads' => array_sum(array_column($allKpiData, 'total_leads')),
            'total_visits' => array_sum(array_column($allKpiData, 'total_visits')),
            'total_bookings' => array_sum(array_column($allKpiData, 'total_bookings')),
            'total_money' => array_sum(array_column($allKpiData, 'total_money')),
            'avg_conversion' => count($allKpiData) > 0 ? round(array_sum(array_column($allKpiData, 'conversion_rate')) / count($allKpiData), 2) : 0,
            'total_users' => count($allKpiData)
        ];

        $executives = [];
        foreach ($allKpiData as $item) {
            $executives[$item['user']['id']] = $item['user'];
        }
        $executives = array_values($executives);

        $queryParams = [];
        if ($filters['search'] !== '') {
            $queryParams['q'] = $filters['search'];
        }
        if ($filters['executive']) {
            $queryParams['executive'] = $filters['executive'];
        }
        if ($filters['year']) {
            $queryParams['year'] = $filters['year'];
        }
        if ($filters['month']) {
            $queryParams['month'] = $filters['month'];
        }
        if ($filters['has_bookings']) {
            $queryParams['has_bookings'] = 1;
        }
        if ($filters['has_visits']) {
            $queryParams['has_visits'] = 1;
        }

        $queryString = http_build_query($queryParams);
        $paginationRoute = $GLOBALS['base_url'] . '/sales-team-performance' . ($queryString ? '?' . $queryString : '');

        return $this->view(
            'sales-developments/sales-team-kpi',
            [
                'current_route' => 'sales-team-performance',
                'page_title' => 'Sales Team Performance',
                'kpiData' => $kpiData,
                'summary' => $summary,
                'executives' => $executives,
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'selectedExecutive' => $filters['executive'],
                'selectedYear' => $filters['year'],
                'selectedMonth' => $filters['month'],
                'searchTerm' => $filters['search'],
                'hasBookings' => $filters['has_bookings'],
                'hasVisits' => $filters['has_visits'],
                'paginationRoute' => $paginationRoute
            ]
        );
    }
}
