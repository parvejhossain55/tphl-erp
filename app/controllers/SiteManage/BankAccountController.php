<?php
namespace SiteManage;
use Controller;
use CompanyBankAccount;
use Location;

class BankAccountController extends Controller
{
    protected $db;
    protected $bankModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->bankModel = new CompanyBankAccount($this->db);
    }

    public function index()
    {
        // Get all clients (booked leads with their details)
        $accounts = $this->bankModel->getAll();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->view("site-manage/companyBankAccounts/bank-accounts", [
            "current_route" => "company-bank-accounts",
            "page_title" => "Bank Accounts Management",
            "accounts" => $accounts,
        ]);
    }
    public function store()
    {
        // Get all clients (booked leads with their details)
        

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $data = [
            'bank_name' => $_POST['bank_name'],
            'account_name' => $_POST['account_name'],
            'account_number' => $_POST['account_number'],
            'status'  => $_POST['status'] ?? 'active',
            'created_by'  => $_SESSION['user']['id'],
        ];
        if($this->bankModel->create($data)){
            setSuccess('Bank Account create Successfully');
            header("Location:{$GLOBALS['base_url']}/company-bank-accounts");
        }else{
              setError('Bank Account created Failed');
            header("Location:{$GLOBALS['base_url']}/company-bank-accounts");
        }
    }
public function update()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $data = [
        'id' => $_POST['id'], // 🔥 MUST
        'bank_name' => $_POST['bank_name'],
        'account_name' => $_POST['account_name'],
        'account_number' => $_POST['account_number'],
        'status' => $_POST['status'], // active | inactive
        'created_by' => $_SESSION['user']['id'],
    ];

    if ($this->bankModel->update($data)) {
        setSuccess('Bank Account Updated Successfully');
    } else {
        setError('Bank Account Update Failed');
    }

    header("Location: {$GLOBALS['base_url']}/company-bank-accounts");
    exit;
}

public function delete(){
    $id = $_POST['id'] ?? 0;

            if ($id && $this->bankModel->delete($id)) {
            setSuccess("Bank account deleted successfully.");
            header("Location: {$GLOBALS['base_url']}/company-bank-accounts");
            exit;
        } else {
              setError('Bank Account created Failed');
                   header("Location: {$GLOBALS['base_url']}/company-bank-accounts");
        }
}


}
