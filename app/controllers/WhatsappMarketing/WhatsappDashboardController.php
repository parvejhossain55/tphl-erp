<?php
<<<<<<< HEAD
namespace WhatsappMarketing;
use Controller;
=======

namespace WhatsappMarketing;

use Controller;
use Exception;
use Lead;
use Project;

>>>>>>> 0bae39f2c2b98536dd73f392610ac427874b8ca5
class WhatsappDashboardController extends Controller
{
    protected $db;
    protected $leadModel;
    protected $taskModel;
    protected $followUpModel;
    protected $bookingModel;
<<<<<<< HEAD
    protected $userModel;
=======
    protected $projectModel;
>>>>>>> 0bae39f2c2b98536dd73f392610ac427874b8ca5
    protected $salesTargetModel;

    public function __construct($db)
    {
        $this->db = $db;
<<<<<<< HEAD
       
=======
        $this->leadModel = new Lead($db);
        $this->projectModel = new Project($db);
>>>>>>> 0bae39f2c2b98536dd73f392610ac427874b8ca5
    }

    public function dashboard()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
<<<<<<< HEAD
   
        return $this->view('whatsapp_marketing/whatsapp-dashboard',[
            "current_route"   => "whatsapp-dashboard",]);
        
        }
=======

        return $this->view('whatsapp_marketing/whatsapp-dashboard', [
            "current_route"   => "whatsapp-dashboard",
        ]);
    }
    public function contactLeads()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $projects = $this->projectModel->getAll();
        $search = $_GET['q'] ?? '';
        $selectedProject = $_GET['project_id'] ?? '';
        $contactLeads = $this->leadModel->getAllLeadsWithProjectContact($selectedProject, $search);
        return $this->view(
            'whatsapp_marketing/contacts-whatsapp',
            [
                "current_route" => "contacts-whatsapp",
                "contactLeads" => $contactLeads,
                "search" => $search,
                "projects" => $projects,
                "selectedProject" => $selectedProject
            ]
        );
    }

    public function updateCampaignAction()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Get POST data
        $leadId = isset($_POST['lead_id']) ? (int) $_POST['lead_id'] : 0;
        $campaignAction = isset($_POST['campaign_status']) ? (int) $_POST['campaign_status'] : null;

        if (!$leadId) {
            setError("Invalid Lead ID.");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        // Check lead exists
        $stmt = $this->db->prepare("SELECT id FROM leads WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $leadId);
        $stmt->execute();
        $result = $stmt->get_result();
        $lead = $result->fetch_assoc();

        if (!$lead) {
            setError("Lead not found.");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        // Update campaign status
        try {

            $stmtUpdate = $this->db->prepare("
            UPDATE leads 
            SET is_send_message = ?, updated_at = NOW()
            WHERE id = ?
        ");

            $stmtUpdate->bind_param("ii", $campaignAction, $leadId);
            $stmtUpdate->execute();

            setSuccess("Campaign status updated successfully!");
        } catch (Exception $e) {

            setError("Failed to update campaign status.");
        }

        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }


    public function reports()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $this->view('whatsapp_marketing/reports-campaign', [
            "current_route"   => "report-campaign",
        ]);
    }
>>>>>>> 0bae39f2c2b98536dd73f392610ac427874b8ca5
}
