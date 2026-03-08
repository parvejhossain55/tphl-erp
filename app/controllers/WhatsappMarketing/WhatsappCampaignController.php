<?php
namespace WhatsappMarketing;
use Controller;
use WhatsappCampaign;


class WhatsappController extends Controller
{
    protected $db;
    protected $campaignModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->campaignModel = new WhatsappCampaign($db);
    }

    // Dashboard index
    public function index()
    {
        $campaigns = $this->campaignModel->getAllWithStats();
        $analytics = $this->campaignModel->getAnalyticsSummary();

        $this->view("whatsapp/dashboard", [
            "page_title" => "WhatsApp Campaigns",
            "campaigns" => $campaigns,
            "analytics" => $analytics
        ]);
    }
}