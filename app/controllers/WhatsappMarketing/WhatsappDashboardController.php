<?php
namespace WhatsappMarketing;
use Controller;
class WhatsappDashboardController extends Controller
{
    protected $db;
    protected $leadModel;
    protected $taskModel;
    protected $followUpModel;
    protected $bookingModel;
    protected $userModel;
    protected $salesTargetModel;

    public function __construct($db)
    {
        $this->db = $db;
       
    }

    public function dashboard()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
   
        return $this->view('whatsapp_marketing/whatsapp-dashboard',[
            "current_route"   => "whatsapp-dashboard",]);
        
        }
    public function contactLeads()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
   
        return $this->view('whatsapp_marketing/contacts-whatsapp',[
            "current_route"   => "contacts-whatsapp",]);
        
        }
    public function campaigns()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
   
        return $this->view('whatsapp_marketing/campaign-contacts',[
            "current_route"   => "campaign-contacts",]);
        
        }
    public function reports()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
   
        return $this->view('whatsapp_marketing/reports-campaign',[
            "current_route"   => "report-campaign",]);
        
        }
}
