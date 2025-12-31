<?php
class BankAccountController extends Controller
{
    protected $db;
    protected $clientModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->clientModel = new AccountClient($this->db);
    }

    public function index()
    {
        // Get all clients (booked leads with their details)
        

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->view("", [
            "current_route" => "company-bank-accounts",
            "page_title" => "Bank Accounts Management",
        ]);
    }
    public function store()
    {
        // Get all clients (booked leads with their details)
        

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->view("accounts/cashIn/clients", [
            "current_route" => "company-bank-accounts",
            "page_title" => "Bank Accounts Management",
        ]);
    }

}
