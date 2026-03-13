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
}
