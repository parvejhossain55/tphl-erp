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
        $teamId = $_GET['team_id'] ?? null;

        if (isset($_SESSION['user']['role_id']) && $_SESSION['user']['role_id'] == 10) {
            if ($teamId) {
                $leads = $this->leadModel->getLeadsByTeamId($teamId);
            } else {
                $leads = $this->leadModel->getAllMarketingLeads(30); // 30 days default
            }
        }

        return $this->view("digital-marketing-team/digital-marketing-head-dashboard", [
            "current_route"       => "digital-marketing-dashboard",
            "page_title"          => "Digital Marketing Dashboard",
            "teams"               => $teams,
            "stats"               => $stats,
            "leads"               => $leads,
            "selected_team_id"    => $teamId
        ]);
    }

    public function executiveDashboard()
    {
         $stats = $this->leadModel->getDigitalMarketingStats();
        $teams = $this->teamsModel->getAll();
        $leads = [];
        $teamId = $_GET['team_id'] ?? null;

        if (isset($_SESSION['user']['role_id']) && $_SESSION['user']['role_id'] == 11) {
            if ($teamId) {
                $leads = $this->leadModel->getLeadsByTeamId($teamId);
            } else {
                $leads = $this->leadModel->getLeadsByCreatedByLast7Days($_SESSION['user']['id']);
            }
        }

        return $this->view("digital-marketing-team/digital-marketing-executive", [
            "current_route"       => "digital-marketing-executive-dashboard",
            "page_title"          => "Digital Marketing Executive Dashboard",
            "teams"               => $teams,
            "stats"               => $stats,
            "leads"               => $leads,
            "selected_team_id"    => $teamId
        ]);
    }

    public function getLeadsByTeam()
{
    $teamId = $_GET['team_id'] ?? null;

    if (!$teamId) {
        echo json_encode([]);
        return;
    }

    $leads = $this->leadModel->getLeadsByTeamId($teamId);

    header('Content-Type: application/json');
    echo json_encode($leads);
}
}