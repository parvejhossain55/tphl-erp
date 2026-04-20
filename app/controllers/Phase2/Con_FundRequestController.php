<?php
namespace Phase2;

use Controller;
use Con_FundRequest;
use Con_Operator;
use ApprovalRequest;

class Con_FundRequestController extends Controller

{
    protected $db;
    protected $fundModel;
    protected $operatorModel;
    protected $approvalModel;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->fundModel     = new Con_FundRequest($this->db);
        $this->operatorModel = new Con_Operator($this->db);
        $this->approvalModel = new ApprovalRequest($this->db);
    }

    /* =====================
       List
    ====================== */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $requests = $this->fundModel->getAllWithRelations();

        $this->view('phase2/fund-requests/fund-lists', [
            'current_route' => 'con-fund-requests',
            'page_title'    => 'Operational Fund Requests',
            'requests'      => $requests,
        ]);
    }

    /* =====================
       Create Form
    ====================== */
    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $operators = $this->operatorModel->getAll();
        $projects  = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/fund-requests/create-fund', [
            'current_route' => 'con-fund-requests',
            'page_title'    => 'New Fund Request',
            'operators'     => $operators,
            'projects'      => $projects,
        ]);
    }

    /* =====================
       Store
       FLOW: pending → (auto create approval_request) → approved → paid
       NO verification step — goes directly to approval
    ====================== */
    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'project_id'   => $_POST['project_id']  ?? null,
            'work_plan_id' => !empty($_POST['work_plan_id']) ? (int)$_POST['work_plan_id'] : null,
            'requester_id' => $_SESSION['user']['id'] ?? null,
            'operator_id'  => !empty($_POST['operator_id']) ? $_POST['operator_id'] : null,
            'amount'       => $_POST['amount']       ?? 0,
            'purpose'      => trim($_POST['purpose'] ?? ''),
            'fund_date'    => $_POST['fund_date']    ?? date('Y-m-d'),
            'status'       => 'pending',
            'created_by'   => $_SESSION['user']['id'] ?? null,
        ];

        if (empty($data['project_id']) || empty($data['purpose']) || empty($data['amount'])) {
            setError('Project, Purpose and Amount are required.');
            header("Location: {$GLOBALS['base_url']}/con-fund-request-create");
            exit;
        }

        $fundId = $this->fundModel->create($data);

        if ($fundId) {
            $project = $this->db->query("SELECT name FROM projects WHERE id = " . (int)$data['project_id'])->fetch_assoc();

            // Auto-create approval_request — goes to management
            $this->approvalModel->create([
                'department'    => 'construction',
                'request_type'  => 'operational_fund',
                'title'         => 'Fund Request — ' . $data['purpose'] . ' | ' . ($project['name'] ?? ''),
                'source_table'  => 'operational_fund_requests',
                'source_id'     => $fundId,
                'requested_days'=> 0,
                'requested_by'  => $_SESSION['user']['id'] ?? null,
                'approve_role'  => 'management',
                'meta_data'     => null,
                'request_amount'=> $data['amount'],
            ]);

            setSuccess('Fund request submitted for approval!');
            header("Location: {$GLOBALS['base_url']}/con-fund-requests");
            exit;
        }

        setError('Failed to submit fund request.');
        header("Location: {$GLOBALS['base_url']}/con-fund-request-create");
        exit;
    }

    /* =====================
       Show Detail
    ====================== */
    public function show($id)
    {
        $request = $this->fundModel->findWithRelations($id);
        if (!$request) {
            setError('Fund request not found.');
            header("Location: {$GLOBALS['base_url']}/con-fund-requests");
            exit;
        }

        $this->view('phase2/fund-requests/show-fund', [
            'current_route' => 'con-fund-requests',
            'page_title'    => 'Fund Request Detail',
            'request'       => $request,
        ]);
    }
}
