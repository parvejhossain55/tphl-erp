<?php

class Con_DashboardController extends Controller
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Quick stats
        $stats = [];

        $stats['total_contractors'] = $this->db
            ->query("SELECT COUNT(*) AS cnt FROM operations_operators WHERE status='active'")->fetch_assoc()['cnt'] ?? 0;

        $stats['pending_bills'] = $this->db
            ->query("SELECT COUNT(*) AS cnt FROM contractor_bill_requests WHERE status IN ('submitted','verified')")->fetch_assoc()['cnt'] ?? 0;

        $stats['pending_funds'] = $this->db
            ->query("SELECT COUNT(*) AS cnt FROM operational_fund_requests WHERE status='pending'")->fetch_assoc()['cnt'] ?? 0;

        $stats['total_vendors'] = $this->db
            ->query("SELECT COUNT(*) AS cnt FROM vendors WHERE status='active'")->fetch_assoc()['cnt'] ?? 0;

        // Recent contractor bills (last 5)
        $recentBills = $this->db->query("
            SELECT cbr.*, oo.name AS operator_name, p.name AS project_name
            FROM contractor_bill_requests cbr
            LEFT JOIN operations_operators oo ON oo.id = cbr.operator_id
            LEFT JOIN projects p ON p.id = cbr.project_id
            ORDER BY cbr.created_at DESC
            LIMIT 5
        ")->fetch_all(MYSQLI_ASSOC);

        // Recent fund requests (last 5)
        $recentFunds = $this->db->query("
            SELECT ofr.*, p.name AS project_name, u.full_name AS requester_name
            FROM operational_fund_requests ofr
            LEFT JOIN projects p ON p.id = ofr.project_id
            LEFT JOIN users u ON u.id = ofr.requester_id
            ORDER BY ofr.created_at DESC
            LIMIT 5
        ")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/dashboard/cons-dashboard', [
            'current_route' => 'con-dashboard',
            'page_title'    => 'Construction Dashboard',
            'stats'         => $stats,
            'recent_bills'  => $recentBills,
            'recent_funds'  => $recentFunds,
        ]);
    }
}
