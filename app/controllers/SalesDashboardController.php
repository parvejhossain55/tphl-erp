<?php

class SalesDashboardController extends Controller
{
    protected $db;
    protected $leadModel;
    protected $followUpModel;
    protected $bookingModel;
    protected $paymentModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->leadModel = new Lead($this->db);
        $this->followUpModel = new FollowUp($this->db);
        $this->bookingModel = new BookingLead($this->db);
        $this->paymentModel = new PaymentLeadBooking($this->db);

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
        $followUpsQuery = "SELECT COUNT(*) AS due FROM lead_follow_ups WHERE follow_up_date <= CURDATE() AND status = 'pending'";
        $followUpsResult = $this->db->query($followUpsQuery);
        $followUpsDue = $followUpsResult->fetch_assoc()['due'] ?? 0;

        //  Onboarded Clients
        $onboardedQuery = "SELECT COUNT(*) AS onboarded FROM lead_bookings WHERE booking_status = 'booked'";
        $onboardedResult = $this->db->query($onboardedQuery);
        $onboardedClients = $onboardedResult->fetch_assoc()['onboarded'] ?? 0;

        //  Booking Money Collected
        $bookingCollectedQuery = "SELECT SUM(amount) AS total_collected FROM lead_payments WHERE payment_type='booking_money' AND payment_status='paid'";
        $bookingCollectedResult = $this->db->query($bookingCollectedQuery);
        $bookingCollected = $bookingCollectedResult->fetch_assoc()['total_collected'] ?? 0;

        //  Conversion Rate
        $conversionRate = $totalLeadsCount > 0 ? ($onboardedClients / $totalLeadsCount) * 100 : 0;

        //  New Follow-ups Today (optional)
        $newFollowUpsQuery = "SELECT COUNT(*) AS new_followups FROM lead_follow_ups WHERE DATE(created_at) = CURDATE()";
        $newFollowUpsResult = $this->db->query($newFollowUpsQuery);
        $newFollowUpsToday = $newFollowUpsResult->fetch_assoc()['new_followups'] ?? 0;



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
    ORDER BY DATE(payment_date)
";
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
    SELECT DATE_FORMAT(payment_date, '%Y-%m') AS month, SUM(amount) AS total
    FROM lead_payments
    WHERE payment_status='paid' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
    GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
    ORDER BY DATE_FORMAT(payment_date, '%Y-%m')
";
        $yearlySalesResult = $this->db->query($yearlySalesQuery);
        $yearlySales = [];
        while ($row = $yearlySalesResult->fetch_assoc()) {
            $yearlySales[] = [
                'month' => $row['month'],
                'total' => (float)$row['total']
            ];
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

        ]);
    }
}
