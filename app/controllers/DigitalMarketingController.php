<?php
class DigitalMarketingController extends Controller
{
    protected $db;
    protected $leadModel;
    protected $teamsModel;
    public function __construct($db)
    {
        $this->db = $db;
        $this->leadModel = new Lead($this->db);
        $this->teamsModel = new Team($this->db);
    }
    public function index()
    {
        $stats = $this->leadModel->getDigitalMarketingStats();
        $teams = $this->teamsModel->getAll();
        $leads = [];
        if (isset($_SESSION['user']['role_id']) && $_SESSION['user']['role_id'] == 10) {
            $leads = $this->leadModel->getLeadsByCreatedByLast7Days($_SESSION['user']['id']);
        }

        return $this->view("marketing-team-head/digital-marketing-head-dashboard", [
            "current_route"       => "digital-marketing-dashboard",
            "page_title"          => "Digital Marketing Dashboard",
            "teams"               => $teams,
            "stats"               => $stats,
            "leads"               => $leads
        ]);
    }
}