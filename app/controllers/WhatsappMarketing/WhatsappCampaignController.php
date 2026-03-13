<?php

namespace WhatsappMarketing;

use Controller;
use Project;
use WhatsappCampaign;


class WhatsappCampaignController extends Controller
{
    protected $db;
    protected $campaignModel;
    protected $projectModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->campaignModel = new WhatsappCampaign($db);
        $this->projectModel = new Project($db);
    }

    // Dashboard index
    public function index()
    {
        // $campaigns = $this->campaignModel->getAllWithStats();
        // $analytics = $this->campaignModel->getAnalyticsSummary();

        $projects = $this->projectModel->getAll();
        $campaigns = $this->campaignModel->getAllWithStats();
        return $this->view('whatsapp_marketing/campaign-whatsapp', [
            "current_route"   => "campaign-contacts",
            "page_title" => "WhatsApp Campaigns",
            "projects" => $projects,
            "campaigns" => $campaigns,
            // "analytics" => $analytics
        ]);
    }


public function store()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Collect POST data safely
    $data = [
        'name' => $_POST['name'] ?? '',
        'target_project_id' => $_POST['target_project_id'] ?? null,
        'message_type' => $_POST['message_type'] ?? 'text',
        'message_content' => $_POST['message_content'] ?? '',
        'schedule_time' => $_POST['schedule_time'] ?? date('Y-m-d H:i:s'),
        'device' => 'Auto Select' // Always auto select
    ];

    if ($this->campaignModel->create($data)) {
        setSuccess("Campaign created successfully!");
        header("Location: {$GLOBALS['base_url']}/campaign-contacts");
        exit;
    }

    setError("Campaign creation failed");
    header("Location: {$GLOBALS['base_url']}/campaign-contacts");
    exit;
}
}
