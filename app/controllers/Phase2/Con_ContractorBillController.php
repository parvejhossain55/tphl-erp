<?php

class Con_ContractorBillController extends Controller
{
    protected $db;
    protected $billModel;
    protected $operatorModel;
    protected $approvalModel;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->billModel     = new Con_ContractorBill($this->db);
        $this->operatorModel = new Con_Operator($this->db);
        $this->approvalModel = new ApprovalRequest($this->db);
    }

    /* =====================
       List
    ====================== */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $bills = $this->billModel->getAllWithRelations();

        $this->view('phase2/contractor-bills/index', [
            'current_route' => 'con-contractor-bills',
            'page_title'    => 'Contractor Bill Requests',
            'bills'         => $bills,
        ]);
    }

    /* =====================
       Create Form
    ====================== */
    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $operators = $this->operatorModel->getActiveContractors();
        $projects  = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/contractor-bills/create', [
            'current_route' => 'con-contractor-bills',
            'page_title'    => 'New Contractor Bill',
            'operators'     => $operators,
            'projects'      => $projects,
        ]);
    }

    /* =====================
       Store
       FLOW: draft → submitted → (auto create approval_request)
    ====================== */
    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'project_id'       => $_POST['project_id'] ?? null,
            'operator_id'      => $_POST['operator_id'] ?? null,
            'bill_date'        => $_POST['bill_date'] ?? date('Y-m-d'),
            'work_description' => trim($_POST['work_description'] ?? ''),
            'bill_amount'      => $_POST['bill_amount'] ?? 0,
            'attachment_path'  => null,
            'status'           => 'submitted',   // skip draft — submit directly
            'created_by'       => $_SESSION['user']['id'] ?? null,
        ];

        // Handle file upload
        if (!empty($_FILES['attachment']['name'])) {
            $uploadDir = __DIR__ . '/../../../public/uploads/contractor-bills/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = time() . '_' . basename($_FILES['attachment']['name']);
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $filename)) {
                $data['attachment_path'] = 'uploads/contractor-bills/' . $filename;
            }
        }

        if (empty($data['project_id']) || empty($data['operator_id']) || empty($data['work_description'])) {
            setError('Project, Contractor and Work Description are required.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill-create");
            exit;
        }

        $billId = $this->billModel->create($data);

        if ($billId) {
            // Auto-create approval_request so Finance can see it
            $operator = $this->operatorModel->find($data['operator_id']);
            $project  = $this->db->query("SELECT name FROM projects WHERE id = " . (int)$data['project_id'])->fetch_assoc();

            $this->approvalModel->create([
                'department'    => 'construction',
                'request_type'  => 'contractor_bill',
                'title'         => 'Contractor Bill — ' . ($operator['name'] ?? '') . ' | ' . ($project['name'] ?? ''),
                'source_table'  => 'contractor_bill_requests',
                'source_id'     => $billId,
                'requested_days'=> 0,
                'requested_by'  => $_SESSION['user']['id'] ?? null,
                'approve_role'  => 'finance',
                'meta_data'     => null,
                'request_amount'=> $data['bill_amount'],
            ]);

            setSuccess('Contractor bill submitted and sent for approval!');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }

        setError('Failed to submit contractor bill.');
        header("Location: {$GLOBALS['base_url']}/con-contractor-bill-create");
        exit;
    }

    /* =====================
       Show Detail
    ====================== */
    public function show($id)
    {
        $bill = $this->billModel->findWithRelations($id);
        if (!$bill) {
            setError('Bill not found.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }

        $this->view('phase2/contractor-bills/show', [
            'current_route' => 'con-contractor-bills',
            'page_title'    => 'Contractor Bill Detail',
            'bill'          => $bill,
        ]);
    }

    /* =====================
       Verify (Site Engineer / Operation Head)
       draft/submitted → verified
    ====================== */
    public function verify($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'status'            => 'verified',
            'verified_by'       => $_SESSION['user']['id'] ?? null,
            'verified_at'       => date('Y-m-d H:i:s'),
            'verification_note' => trim($_POST['verification_note'] ?? ''),
        ];

        if ($this->billModel->updateStatus($id, $data)) {
            setSuccess('Bill verified successfully!');
        } else {
            setError('Verification failed.');
        }

        header("Location: {$GLOBALS['base_url']}/con-contractor-bill/$id");
        exit;
    }
}
