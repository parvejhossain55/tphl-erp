<?php
namespace Phase2;

use Controller;
use Con_ContractorBill;
use Con_Operator;
use Con_FinancialTransaction;
use ApprovalRequest;
use Con_Contractor;
use AccountMailService;

require_once __DIR__ . '/../../views/accounts/AccountMailService.php';

class Con_ContractorBillController extends Controller
{
    protected $db;
    protected $billModel;
    protected $operatorModel;
    protected $approvalModel;
    protected $txnModel;
    protected $targetModel;
    protected $mailService;
    protected $qcInspectionModel;
    protected $settlementModel;
    protected $userRole;
    protected $userId;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->billModel     = new Con_ContractorBill($this->db);
        $this->operatorModel = new Con_Contractor($this->db);
        $this->approvalModel = new ApprovalRequest($this->db);
        $this->txnModel      = new Con_FinancialTransaction($this->db);
        $this->mailService   = new AccountMailService($this->db);

        require_once __DIR__ . '/../../models/Con_QCInspection.php';
        $this->qcInspectionModel = new \Con_QCInspection($this->db);

        require_once __DIR__ . '/../../models/Con_ContractorBillSettlement.php';
        $this->settlementModel = new \Con_ContractorBillSettlement($this->db);

        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->userId   = $_SESSION['user']['id'] ?? null;
        $this->userRole = isset($_SESSION['user']['role_id']) ? (int)$_SESSION['user']['role_id'] : null;
    }

    // =========================================================================
    // ROLE HELPERS
    // =========================================================================

    /** PM / PM Head (Role 15: PM, Role 22: PM Head, Roles 1, 3: Admin) can PM Approve / Verify */
    private function canApprovePM()
    {
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        if (in_array($this->userRole, [33, 34, 35], true) || in_array($roleName, ['chief officer', 'management viewer', 'seal ceo'], true)) {
            return false;
        }
        return (
            in_array($this->userRole, [1, 3, 15, 22])
            || str_contains($roleName, 'project manager')
            || str_contains($roleName, 'pm head')
            || str_contains($roleName, 'admin')
        );
    }

    /** Final Management Approval: Roles 20 (Admin & Operation Head), 32 (Admin Operation), 1 (Admin), 3 (Super Admin), 38 (Board of Director / DCEO) */
    private function canApproveManagement()
    {
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        if (in_array($this->userRole, [15, 22, 33, 34, 35], true) || in_array($roleName, ['chief officer', 'management viewer', 'seal ceo'], true)) {
            return false;
        }
        return (
            in_array($this->userRole, [1, 3, 38, 20, 32])
            || str_contains($roleName, 'operation head')
            || str_contains($roleName, 'admin & operation head')
            || str_contains($roleName, 'director')
            || str_contains($roleName, 'dceo')
            || str_contains($roleName, 'management')
            || (str_contains($roleName, 'admin') && !str_contains($roleName, 'project'))
        );
    }

    private function canApprove()
    {
        return $this->canApproveManagement();
    }

    /** Roles that can do Purchase Team Leader Review (Role 31) */
    private function canReviewTeamLeader()
    {
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        if (in_array($this->userRole, [33, 34, 35], true) || in_array($roleName, ['chief officer', 'management viewer', 'seal ceo'], true)) {
            return false;
        }
        return (
            in_array($this->userRole, [31, 17, 1, 3], true)
            || str_contains($roleName, 'purchase team leader')
            || str_contains($roleName, 'senior purchase')
            || $this->canApprovePurchaseHead()
        );
    }

    /** Roles that can do Purchase Head Approval (Role 17 only + Management 1, 3) */
    private function canApprovePurchaseHead()
    {
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        if (in_array($this->userRole, [33, 34, 35], true) || in_array($roleName, ['chief officer', 'management viewer', 'seal ceo'], true)) {
            return false;
        }
        return (
            in_array($this->userRole, [17, 1, 3], true)
            || str_contains($roleName, 'purchase head')
            || str_contains($roleName, 'head of purchase')
        );
    }

    /** QC & Internal Audit role or specific permission check */
    private function canApproveQC()
    {
        if (in_array($this->userRole, [22, 20, 33, 34, 35])) return false;
        if (in_array($this->userRole, [1, 3, 24, 25])) return true;
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        if (in_array($this->userRole, [33, 34, 35], true) || in_array($roleName, ['chief officer', 'management viewer', 'seal ceo'], true)) return false;
        return (
            str_contains($roleName, 'qc') 
            || str_contains($roleName, 'quality controller') 
            || str_contains($roleName, 'internal audit') 
            || str_contains($roleName, 'audit')
            || hasPermission('QC & Internal Audit')
            || hasPermission('QC Dashboard')
        );
    }

    /** Roles 1, 3, 6, 30, 38 (Accounts / Management) can process payments */
    private function canProcessPayment()
    {
        $roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        if (in_array($this->userRole, [33, 34, 35], true) || in_array($roleName, ['chief officer', 'management viewer', 'seal ceo'], true)) {
            return false;
        }
        return in_array($this->userRole, [1, 3, 6, 30, 38]) || str_contains($roleName, 'account') || str_contains($roleName, 'management') || str_contains($roleName, 'director') || str_contains($roleName, 'dceo') || str_contains($roleName, 'admin');
    }

    // =========================================================================
    // LIST — role-based filtering
    // =========================================================================

    public function index()
    {
        $filterUserId = null;
        $filterProjectIds = [];

        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        $isGlobalViewRole = in_array($this->userRole, [1, 3, 6, 17, 20, 22, 23, 24, 25, 30, 31, 32, 33])
            || str_contains($roleName, 'admin')
            || str_contains($roleName, 'qc')
            || str_contains($roleName, 'audit')
            || str_contains($roleName, 'operation')
            || str_contains($roleName, 'management')
            || str_contains($roleName, 'pm head')
            || str_contains($roleName, 'accounts');

        if (!$isGlobalViewRole) {
            // Restricted role: show only bills they created
            $filterUserId = $this->userId;

            // ALSO show all bills for projects they are assigned to
            // (site_engineer_id, pm_id, operation_head_id, qc_id, etc.)
            $projStmt = $this->db->prepare("
                SELECT id FROM projects
                WHERE site_engineer_id = ?
                   OR pm_id             = ?
                   OR operation_head_id = ?
                   OR qc_id             = ?
                   OR struct_engineer_id = ?
                   OR draftsman_id       = ?
            ");
            $uid = $this->userId;
            $projStmt->bind_param('iiiiii', $uid, $uid, $uid, $uid, $uid, $uid);
            $projStmt->execute();
            $projResult = $projStmt->get_result();
            while ($row = $projResult->fetch_assoc()) {
                $filterProjectIds[] = (int)$row['id'];
            }
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || (isset($_GET['ajax']) && $_GET['ajax'] == '1');

        $search               = trim($_GET['search'] ?? '');
        $selectedProjectId    = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $selectedCreatorId    = !empty($_GET['created_by']) ? (int)$_GET['created_by'] : null;
        $selectedContractorId = !empty($_GET['contractor_id']) ? (int)$_GET['contractor_id'] : null;
        $status               = trim($_GET['status'] ?? 'All');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPageParam = $_GET['per_page'] ?? 25;
        if ($perPageParam === 'all' || $perPageParam === '-1') {
            $limit = 0;
            $perPage = 'all';
        } else {
            $limit = in_array((int)$perPageParam, [10, 25, 50, 100], true) ? (int)$perPageParam : 25;
            $perPage = $limit;
        }
        $offset = ($limit > 0) ? ($page - 1) * $limit : 0;

        $filters = [
            'role_user_id'     => $filterUserId,
            'role_project_ids' => $filterProjectIds,
            'search'           => $search,
            'project_id'       => $selectedProjectId,
            'created_by'       => $selectedCreatorId,
            'contractor_id'    => $selectedContractorId,
            'status'           => $status
        ];

        $totalRecords = $this->billModel->getFilteredBillsCount($filters);
        $totalPages   = ($limit > 0 && $totalRecords > 0) ? (int)ceil($totalRecords / $limit) : 1;
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
            $offset = ($limit > 0) ? ($page - 1) * $limit : 0;
        }

        $bills        = $this->billModel->getFilteredBills($filters, $limit, $offset);
        $statusCounts = $this->billModel->getStatusCounts($filterUserId, $filterProjectIds);

        // Fetch distinct filter options (always populated with all valid options for filtering)
        $projects = $this->billModel->getDistinctProjects();
        if (empty($projects)) {
            $resP = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC");
            $projects = $resP ? $resP->fetch_all(MYSQLI_ASSOC) : [];
        }

        $creators = $this->billModel->getDistinctCreators();
        if (empty($creators)) {
            $resC = $this->db->query("SELECT DISTINCT u.id, u.full_name FROM users u JOIN contractor_bill_requests cbr ON cbr.created_by = u.id ORDER BY u.full_name ASC");
            $creators = $resC ? $resC->fetch_all(MYSQLI_ASSOC) : [];
        }

        // Fetch contractors with trade/expertise for search & filter
        $resCon = $this->db->query("
            SELECT id, name, role, trade 
            FROM contractors 
            WHERE status = 'active' OR id IN (SELECT DISTINCT operator_id FROM contractor_bill_requests WHERE operator_id IS NOT NULL)
            ORDER BY name ASC
        ");
        $contractors = $resCon ? $resCon->fetch_all(MYSQLI_ASSOC) : [];

        $paginationHtml = $this->generateAjaxPagination($page, $totalPages, $totalRecords, $limit > 0 ? $limit : $totalRecords, 'loadBillsPage');

        $startEntry = $totalRecords > 0 ? ($offset + 1) : 0;
        $endEntry   = ($limit > 0) ? min($totalRecords, $offset + $limit) : $totalRecords;
        $infoText   = "Showing {$startEntry} to {$endEntry} of {$totalRecords} entries";

        if ($isAjax) {
            ob_start();
            $base_url = $GLOBALS['base_url'] ?? '/TPHL-ERP';
            $userRole = $this->userRole;
            include dirname(__DIR__, 2) . '/views/phase2/contractor-bills/partials/bill_rows.php';
            $tableHtml = ob_get_clean();

            header('Content-Type: application/json');
            echo json_encode([
                'success'         => true,
                'table_html'      => $tableHtml,
                'pagination_html' => $paginationHtml,
                'info_text'       => $infoText,
                'total_records'   => $totalRecords,
                'total_pages'     => $totalPages,
                'current_page'    => $page,
                'status_counts'   => $statusCounts
            ]);
            exit;
        }

        $this->view('phase2/contractor-bills/bill-lists', [
            'current_route'        => 'con-contractor-bills',
            'page_title'           => 'Contractor Bill Requests',
            'bills'                => $bills,
            'userRole'             => $this->userRole,
            'contractors'          => $contractors,
            'projects'             => $projects,
            'creators'             => $creators,
            'statusCounts'         => $statusCounts,
            'filters'              => $filters,
            'paginationHtml'       => $paginationHtml,
            'infoText'             => $infoText,
            'totalRecords'         => $totalRecords,
            'totalPages'           => $totalPages,
            'currentPage'          => $page,
            'perPage'              => $perPage,
            'offset'               => $offset,
            'selectedContractorId' => $selectedContractorId,
            'selectedProjectId'    => $selectedProjectId,
            'selectedCreatorId'    => $selectedCreatorId,
        ]);
    }

    /**
     * Generate AJAX pagination HTML
     */
    public function generateAjaxPagination($currentPage, $totalPages, $totalRecords, $perPage, $callbackFn = 'loadBillsPage')
    {
        $perPage = (int)$perPage;
        if ($perPage <= 0 || ($totalPages <= 1 && $totalRecords <= $perPage)) {
            return '';
        }

        $startEntry = $totalRecords > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
        $endEntry = min($totalRecords, $currentPage * $perPage);

        $html = '<div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2 w-100">';
        $html .= '<div class="text-muted fs-13 font-semibold pagination-info-text">';
        $html .= "Showing <span class='text-dark fw-bold'>{$startEntry}</span> to <span class='text-dark fw-bold'>{$endEntry}</span> of <span class='text-dark fw-bold'>{$totalRecords}</span> entries";
        $html .= '</div>';

        $html .= '<nav aria-label="Page navigation">';
        $html .= '<ul class="pagination pagination-sm mb-0 gap-1">';

        // Previous button
        if ($currentPage > 1) {
            $prevPage = $currentPage - 1;
            $html .= "<li class='page-item'><a class='page-link ajax-page-link rounded-2 px-2.5' href='javascript:void(0);' onclick='{$callbackFn}({$prevPage}); return false;' data-page='{$prevPage}'><i class='bi bi-chevron-left me-1'></i>Prev</a></li>";
        } else {
            $html .= "<li class='page-item disabled'><span class='page-link rounded-2 px-2.5 text-muted'><i class='bi bi-chevron-left me-1'></i>Prev</span></li>";
        }

        // Page range calculation
        $pages = [];
        if ($totalPages <= 7) {
            for ($i = 1; $i <= $totalPages; $i++) $pages[] = $i;
        } else {
            if ($currentPage <= 4) {
                $pages = [1, 2, 3, 4, 5, '...', $totalPages];
            } elseif ($currentPage >= $totalPages - 3) {
                $pages = [1, '...', $totalPages - 4, $totalPages - 3, $totalPages - 2, $totalPages - 1, $totalPages];
            } else {
                $pages = [1, '...', $currentPage - 1, $currentPage, $currentPage + 1, '...', $totalPages];
            }
        }

        foreach ($pages as $p) {
            if ($p === '...') {
                $html .= "<li class='page-item disabled'><span class='page-link border-0 px-2'>...</span></li>";
            } elseif ($p == $currentPage) {
                $html .= "<li class='page-item active'><span class='page-link rounded-2 px-3 fw-bold bg-primary text-white border-primary'>{$p}</span></li>";
            } else {
                $html .= "<li class='page-item'><a class='page-link ajax-page-link rounded-2 px-3' href='javascript:void(0);' onclick='{$callbackFn}({$p}); return false;' data-page='{$p}'>{$p}</a></li>";
            }
        }

        // Next button
        if ($currentPage < $totalPages) {
            $nextPage = $currentPage + 1;
            $html .= "<li class='page-item'><a class='page-link ajax-page-link rounded-2 px-2.5' href='javascript:void(0);' onclick='{$callbackFn}({$nextPage}); return false;' data-page='{$nextPage}'>Next<i class='bi bi-chevron-right ms-1'></i></a></li>";
        } else {
            $html .= "<li class='page-item disabled'><span class='page-link rounded-2 px-2.5 text-muted'>Next<i class='bi bi-chevron-right ms-1'></i></span></li>";
        }

        $html .= '</ul>';
        $html .= '</nav>';
        $html .= '</div>';

        return $html;
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function create()
    {
        if (!hasPermission('Contractor Bill') && !in_array($this->userRole, [1, 3, 33, 15, 16, 22, 17, 23, 31, 20, 32, 12, 6, 30])) {
            setError('You do not have permission to create contractor bills.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }
        $operators = $this->operatorModel->getActiveContractors();
        $projects  = $this->getProjects();
        $targets   = [];
        $workTypes = $this->db->query("SELECT id, work_type_name FROM work_types ORDER BY work_type_name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/contractor-bills/create-bill', [
            'current_route' => 'con-contractor-bills',
            'page_title'    => 'New Contractor Bill',
            'operators'     => $operators,
            'projects'      => $projects,
            'targets'       => $targets,
            'workTypes'     => $workTypes,
            'coaList'       => getCOAExpenseList($this->db),
        ]);
    }

    // =========================================================================
    // STORE — auto-create approval_request
    // =========================================================================

    public function store()
    {
        if (!hasPermission('Contractor Bill') && !in_array($this->userRole, [1, 3, 33, 15, 16, 22, 17, 23, 31, 20, 32, 12, 6, 30])) {
            setError('You do not have permission to create contractor bills.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }
        $creatorRole = (int)$this->userRole;
        $initialStatus = in_array($creatorRole, [1, 3, 15, 22, 17, 23, 31], true) ? 'verified' : 'submitted';

        $data = [
            'project_id'       => $_POST['project_id'] ?? null,
            'operator_id'      => $_POST['operator_id'] ?? null,
            'work_order_id'    => !empty($_POST['work_order_id']) ? $_POST['work_order_id'] : null,
            'target_id'        => !empty($_POST['target_id']) ? $_POST['target_id'] : null,
            'bill_type'        => $_POST['bill_type'] ?? 'Progress',
            'bill_date'        => $_POST['bill_date'] ?? date('Y-m-d'),
            'work_description' => trim($_POST['work_description'] ?? ''),
            'bill_amount'      => $_POST['bill_amount'] ?? 0,
            'attachment_path'  => null,
            'status'           => $initialStatus,
            'created_by'       => $this->userId,
            'account_id'       => !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null,
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
            setError('Project, Contractor, and Work Description are required.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill-create");
            exit;
        }

        // Validate Work Order Budget if selected
        if (!empty($data['work_order_id'])) {
            require_once __DIR__ . '/../../models/Con_WorkOrder.php';
            $woModel = new \Con_WorkOrder($this->db);
            $workOrder = $woModel->find($data['work_order_id']);
            if (!$workOrder) {
                setError('Invalid Work Order selected.');
                header("Location: {$GLOBALS['base_url']}/con-contractor-bill-create");
                exit;
            }
            
            $billedSoFarRes = $this->db->query("SELECT COALESCE(SUM(IF(approved_amount IS NOT NULL AND approved_amount > 0, approved_amount, bill_amount)), 0) as total FROM contractor_bill_requests WHERE work_order_id = {$data['work_order_id']} AND status != 'rejected'");
            $billedSoFar = $billedSoFarRes ? (float)$billedSoFarRes->fetch_assoc()['total'] : 0;
            
            $remainingWOAmount = (float)$workOrder['total_amount'] - $billedSoFar;
            if ($data['bill_amount'] > $remainingWOAmount) {
                setError('Bill amount (৳' . number_format($data['bill_amount'], 2) . ') exceeds remaining Work Order limit (৳' . number_format($remainingWOAmount, 2) . ').');
                header("Location: {$GLOBALS['base_url']}/con-contractor-bill-create");
                exit;
            }
        }

        // Validate Target if selected (Strict Financial Limit for Progress Bills)
        if (!empty($data['target_id'])) {
            $target = $this->findTarget($data['target_id'], $data['work_order_id']);
            if ($target) {
                $gainPct = (float)$target['gain_percentage'];
                $targetPct = (float)$target['target_percentage'];
                
                if ($gainPct <= 0) {
                    setError('Selected target must have a Gain % greater than 0 to submit a bill.');
                    header("Location: {$GLOBALS['base_url']}/con-contractor-bill-create");
                    exit;
                }

                // If this target represents a specific % of the Work Order, enforce financial limit
                if ($targetPct > 0 && $data['bill_type'] === 'Progress') {
                    // Maximum allowable amount for this target: (WO Total * Target%) * Gain%
                    $maxTargetAmount = ((float)$workOrder['total_amount'] * ($targetPct / 100)) * ($gainPct / 100);
                    
                    // Find out how much they already billed specifically for this target
                    $targetBilledRes = $this->db->query("SELECT COALESCE(SUM(IF(approved_amount IS NOT NULL AND approved_amount > 0, approved_amount, bill_amount)), 0) as total FROM contractor_bill_requests WHERE target_id = {$data['target_id']} AND status != 'rejected'");
                    $targetBilledSoFar = $targetBilledRes ? (float)$targetBilledRes->fetch_assoc()['total'] : 0;
                    
                    $remainingTargetAmount = $maxTargetAmount - $targetBilledSoFar;
                    
                    // Use a tiny 1-taka margin for float rounding issues
                    if ($data['bill_amount'] > ($remainingTargetAmount + 1.00)) {
                        setError('Bill amount (৳' . number_format($data['bill_amount'], 2) . ') exceeds the completed work value for this target. Maximum allowable request is ৳' . number_format($remainingTargetAmount, 2) . '.');
                        header("Location: {$GLOBALS['base_url']}/con-contractor-bill-create");
                        exit;
                    }
                }
            }
        }


        $billId = $this->billModel->create($data);

        if ($billId) {
            // Auto-populate verification/purchase columns based on submitter's role
            if (in_array($creatorRole, [1, 3, 17], true)) {
                $this->db->query("UPDATE contractor_bill_requests SET verified_by = {$this->userId}, verified_at = NOW(), verification_note = 'Auto-verified upon submission', purchase_head_approved_by_id = {$this->userId}, purchase_head_approved_at = NOW(), purchase_head_note = 'Submitted directly by Head of Purchase / Management' WHERE id = " . (int)$billId);
                $initialApproveRole = 'qc';
            } elseif ($creatorRole === 31) {
                $this->db->query("UPDATE contractor_bill_requests SET verified_by = {$this->userId}, verified_at = NOW(), verification_note = 'Submitted by Purchase Team Leader', purchase_team_leader_by_id = {$this->userId}, purchase_team_leader_at = NOW(), purchase_team_leader_note = 'Submitted by Purchase Team Leader' WHERE id = " . (int)$billId);
                $initialApproveRole = 'purchase_head';
            } elseif (in_array($creatorRole, [15, 22, 23], true)) {
                $vNote = in_array($creatorRole, [15, 22], true) ? 'Submitted directly by Project Manager' : 'Submitted by Purchase Executive';
                $this->db->query("UPDATE contractor_bill_requests SET verified_by = {$this->userId}, verified_at = NOW(), verification_note = '{$vNote}' WHERE id = " . (int)$billId);
                $initialApproveRole = 'purchase_head';
            } else {
                $initialApproveRole = 'pm';
            }

            $operator = $this->operatorModel->find($data['operator_id']);
            $project  = $this->db->query("SELECT name FROM projects WHERE id = " . (int)$data['project_id'])->fetch_assoc();

            $this->approvalModel->create([
                'department'    => 'construction',
                'request_type'  => 'contractor_bill',
                'title'         => 'Contractor Bill CB-' . $billId . ' — ' . ($operator['name'] ?? '') . ' | ' . ($project['name'] ?? ''),
                'source_table'  => 'contractor_bill_requests',
                'source_id'     => $billId,
                'requested_days'=> 0,
                'requested_by'  => $this->userId,
                'approve_role'  => $initialApproveRole,
                'meta_data'     => null,
                'request_amount'=> $data['bill_amount'],
            ]);

            // Send email notification to Management + Accounts
            try {
                $this->mailService->sendContractorBillOnSubmission([
                    'bill_amount'      => $data['bill_amount'],
                    'contractor_name'  => $operator['name'] ?? '',
                    'work_description' => $data['work_description'],
                    'project_name'     => $project['name'] ?? '',
                    'project_id'       => $data['project_id'],
                    'created_by'       => $this->userId,
                ]);
            } catch (\Exception $e) {
                error_log("Contractor Bill email (submission) failed: " . $e->getMessage());
            }

            setSuccess('Contractor bill submitted and sent for approval!');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$billId}");
            exit;
        }

        setError('Failed to submit contractor bill.');
        header("Location: {$GLOBALS['base_url']}/con-contractor-bill-create");
        exit;
    }

    // =========================================================================
    // EDIT
    // =========================================================================

    public function edit($id)
    {
        if (!hasPermission('Contractor Bill') && !in_array($this->userRole, [1, 3, 33, 15, 16, 22, 17, 23, 31, 20, 32, 12, 6, 30])) {
            setError('You do not have permission to edit contractor bills.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }

        $bill = $this->billModel->find($id);
        if (!$bill) {
            setError('Bill not found.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }

        if (in_array($bill['status'], ['approved', 'paid', 'partially_paid', 'fully_paid'])) {
            setError('Approved or paid bills cannot be edited.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }

        $operators = $this->operatorModel->getActiveContractors();
        $projects  = $this->getProjects();
        $targets   = [];
        $workTypes = $this->db->query("SELECT id, work_type_name FROM work_types ORDER BY work_type_name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/contractor-bills/edit-bill', [
            'current_route' => 'con-contractor-bills',
            'page_title'    => 'Edit Contractor Bill',
            'bill'          => $bill,
            'operators'     => $operators,
            'projects'      => $projects,
            'targets'       => $targets,
            'workTypes'     => $workTypes,
            'coaList'       => getCOAExpenseList($this->db)
        ]);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update($id)
    {
        if (!hasPermission('Contractor Bill') && !in_array($this->userRole, [1, 3, 33, 15, 16, 22, 17, 23, 31, 20, 32, 12, 6, 30])) {
            setError('You do not have permission to edit contractor bills.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }

        $bill = $this->billModel->find($id);
        if (!$bill) {
            setError('Bill not found.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }

        if (in_array($bill['status'], ['approved', 'paid', 'partially_paid', 'fully_paid'])) {
            setError('Approved or paid bills cannot be edited.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }

        $data = [
            'project_id'       => $_POST['project_id'] ?? null,
            'operator_id'      => $_POST['operator_id'] ?? null,
            'work_order_id'    => !empty($_POST['work_order_id']) ? $_POST['work_order_id'] : null,
            'target_id'        => !empty($_POST['target_id']) ? $_POST['target_id'] : null,
            'bill_type'        => $_POST['bill_type'] ?? 'Progress',
            'bill_date'        => $_POST['bill_date'] ?? date('Y-m-d'),
            'work_description' => trim($_POST['work_description'] ?? ''),
            'bill_amount'      => $_POST['bill_amount'] ?? 0,
            'account_id'       => !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null,
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
            setError('Project, Contractor, and Work Description are required.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill-edit/{$id}");
            exit;
        }

        $this->billModel->update($id, $data);
        
        // Also update the approval_request amount if it exists and hasn't been finally approved yet
        $this->db->query("UPDATE approval_requests SET request_amount = " . (float)$data['bill_amount'] . " WHERE source_table = 'contractor_bill_requests' AND source_id = " . (int)$id . " AND status IN ('pending', 'in_review')");

        setSuccess('Contractor bill updated successfully!');
        header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
        exit;
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    public function delete($id)
    {
        if (!hasPermission('Contractor Bill') && !in_array($this->userRole, [1, 3, 33, 17, 23, 31, 20, 32, 12, 6, 30])) {
            setError('You do not have permission to delete contractor bills.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }

        $bill = $this->billModel->find($id);
        if (!$bill) {
            setError('Bill not found.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }

        if (in_array($bill['status'], ['approved', 'paid', 'partially_paid', 'fully_paid'])) {
            setError('Approved or paid bills cannot be deleted.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }

        $this->billModel->delete($id);
        
        // delete associated approval request
        $this->db->query("DELETE FROM approval_requests WHERE source_table = 'contractor_bill_requests' AND source_id = " . (int)$id);

        setSuccess('Contractor bill deleted successfully!');
        header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
        exit;
    }

    // =========================================================================
    // SHOW — Enhanced single-page experience
    // =========================================================================

    public function show($id)
    {
        $bill = $this->billModel->findWithRelations($id);
        if (!$bill) {
            setError('Bill not found.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }

        // Get payment history (con_financial_transactions)
        $payments   = $this->txnModel->getByRef('contractor_bill', $id);
        $totalPaid  = $this->txnModel->getTotalPaidForSource('contractor_bill', $id);
        $payableAmt = (float)($bill['approved_amount'] ?? $bill['bill_amount'] ?? 0);
        $dueAmount  = max(0, $payableAmt - $totalPaid);

        // Get linked approval_request
        $approvalReq = $this->getApprovalRequest($id);

        // Get banks for payment modal
        $resBanks = $this->db->query("SELECT id, bank_name, CONCAT(account_name, IF(account_number != '', CONCAT(' (', account_number, ')'), '')) as branch_name FROM company_bank_accounts WHERE status = 'active' ORDER BY is_default DESC, bank_name ASC");
        $banks = $resBanks ? $resBanks->fetch_all(MYSQLI_ASSOC) : [];

        // Fetch weekly progress items for the target if linked
        $targetItems = [];
        $monthlyTargetItems = [];
        if (!empty($bill['target_id'])) {
            $stmt = $this->db->prepare("
                SELECT pwpi.*, pmti.work_item_name, pmti.unit, c.name AS contractor_name
                FROM phase_weekly_progress_items pwpi
                INNER JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
                LEFT JOIN contractors c ON c.id = pwpi.contractor_id
                WHERE pwpi.weekly_progress_id = ?
            ");
            if ($stmt) {
                $stmt->bind_param('i', $bill['target_id']);
                $stmt->execute();
                $resTi = $stmt->get_result();
                $targetItems = $resTi ? $resTi->fetch_all(MYSQLI_ASSOC) : [];
            }

            // Fetch monthly target items
            $stmtM = $this->db->prepare("
                SELECT pmti.*, wt.work_type_name
                FROM phase_monthly_target_items pmti
                LEFT JOIN work_types wt ON wt.id = pmti.work_type_id
                WHERE pmti.target_id = (SELECT target_id FROM phase_weekly_progress WHERE id = ? LIMIT 1)
                ORDER BY pmti.sort_order ASC, pmti.id ASC
            ");
            if ($stmtM) {
                $stmtM->bind_param('i', $bill['target_id']);
                $stmtM->execute();
                $resMti = $stmtM->get_result();
                $monthlyTargetItems = $resMti ? $resMti->fetch_all(MYSQLI_ASSOC) : [];
            }
        }

        // Load settlements for this bill
        $settlements          = $this->settlementModel->getByBillId((int)$id);
        $totalPaidToContractor = $this->settlementModel->getTotalPaidByBill((int)$id);

        // Fetch linked expense adjustments (Central Accounts Settlements)
        $expenseAdjustments = [];
        $stmtAdj = $this->db->prepare("
            SELECT ea.*, u.full_name as requester_name 
            FROM expense_adjustments ea 
            JOIN users u ON ea.user_id = u.id 
            WHERE ea.cashout_request_id = ? AND ea.parent_type = 'contractor'
            ORDER BY ea.id DESC
        ");
        if ($stmtAdj) {
            $stmtAdj->bind_param('i', $id);
            $stmtAdj->execute();
            $expenseAdjustments = $stmtAdj->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $this->view('phase2/contractor-bills/show-bill', [
            'current_route'           => 'con-contractor-bills',
            'page_title'              => 'Contractor Bill Detail',
            'bill'                    => $bill,
            'payments'                => $payments,
            'total_paid'              => $totalPaid,
            'due_amount'              => $dueAmount,
            'payable_amount'          => $payableAmt,
            'approval_request'        => $approvalReq,
            'canApprovePM'            => $this->canApprovePM(),
            'canApprovePurchaseHead'  => $this->canApprovePurchaseHead(),
            'canReviewTeamLeader'     => $this->canReviewTeamLeader(),
            'canApproveQC'            => $this->canApproveQC(),
            'canApproveManagement'    => $this->canApproveManagement(),
            'canApprove'              => $this->canApproveManagement(),
            'canProcessPayment'       => $this->canProcessPayment(),
            'userRole'                => $this->userRole,
            'banks'                   => $banks,
            'target_items'            => $targetItems,
            'monthly_target_items'    => $monthlyTargetItems,
            'settlements'             => $settlements,
            'total_paid_to_contractor'=> $totalPaidToContractor,
            'current_user_id'         => $this->userId,
            'expense_adjustments'     => $expenseAdjustments,
        ]);
    }

    // =========================================================================
    // VERIFY / PM APPROVE (Project Manager / PM Head)
    // =========================================================================

    public function verify($id)
    {
        if (!$this->canApprovePM()) {
            setError('You are not authorized for Dept. Head approval.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/$id");
            exit;
        }

        $billRow = $this->billModel->find($id);
        $note = trim($_POST['verification_note'] ?? '');
        $data = [
            'status'            => 'verified',
            'verified_by'       => $this->userId,
            'verified_at'       => date('Y-m-d H:i:s'),
            'verification_note' => $note,
        ];

        if ($this->billModel->updateStatus($id, $data)) {
            require_once __DIR__ . '/../../models/Con_ApprovalLog.php';
            $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
            $userName = $_SESSION['user']['full_name'] ?? 'User';
            $attr = \Con_ApprovalLog::formatApprovalAttribution($roleId, $userName, 'approved');
            $logModel = new \Con_ApprovalLog($this->db);
            $logModel->logConstructionApproval(
                'contractor_bill_requests',
                (int)$id,
                'Dept. Head Approval',
                'approved',
                isset($billRow['project_id']) ? (int)$billRow['project_id'] : null,
                (float)($billRow['bill_amount'] ?? 0),
                $note
            );

            // Forward approval request to Head of Purchase (Role 17)
            $stmtAr = $this->db->prepare("
                UPDATE approval_requests 
                SET approve_role = 'purchase_head', 
                    status = 'pending', 
                    approval_note = ?
                WHERE source_table = 'contractor_bill_requests' AND source_id = ?
            ");
            if ($stmtAr) {
                $pmNote = $attr['approval_label'] . (!empty($note) ? ": {$note}" : "");
                $stmtAr->bind_param('si', $pmNote, $id);
                $stmtAr->execute();
                $stmtAr->close();
            }
            setSuccess($attr['approval_label'] . ' & forwarded to Head of Purchase (Role 17)!');
        } else {
            setError('Verification failed.');
        }

        header("Location: {$GLOBALS['base_url']}/con-contractor-bill/$id");
        exit;
    }

    // =========================================================================
    // PURCHASE TEAM LEADER REVIEW (Role 31 Optional Step)
    // =========================================================================

    public function reviewTeamLeader($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        if (!$this->canReviewTeamLeader()) {
            setError('You are not authorized to review as Purchase Team Leader.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $bill = $this->billModel->find($id);
        if (!$bill || !in_array($bill['status'], ['submitted', 'verified'])) {
            setError('Invalid bill status for Team Leader review.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $note = trim($_POST['tl_note'] ?? '');
        if ($this->billModel->reviewPurchaseTeamLeader($id, $this->userId, $note)) {
            $tlNoteFull = "Reviewed & Forwarded by Purchase Team Leader. " . ($note ? "Note: {$note}" : "");
            $stmtAr = $this->db->prepare(
                "UPDATE approval_requests
                 SET approve_role = 'purchase_head',
                     status = 'pending',
                     approval_note = ?
                 WHERE source_table = 'contractor_bill_requests' AND source_id = ? AND status = 'pending'"
            );
            if ($stmtAr) {
                $stmtAr->bind_param('si', $tlNoteFull, $id);
                $stmtAr->execute();
                $stmtAr->close();
            }
            setSuccess('Contractor Bill reviewed and forwarded to Head of Purchase (Role 17).');
        } else {
            setError('Failed to record Team Leader review.');
        }

        header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
        exit;
    }

    // =========================================================================
    // HEAD OF PURCHASE APPROVE (Role 17 Mandatory Step)
    // =========================================================================

    public function approvePurchaseHead($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        if (!$this->canApprovePurchaseHead()) {
            setError('You are not authorized to perform Head of Purchase approval.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $bill = $this->billModel->find($id);
        if (!$bill || !in_array($bill['status'], ['submitted', 'verified'])) {
            setError('Invalid bill status for Head of Purchase approval.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $isMgmtUser = in_array((int)$this->userRole, [1, 3], true);
        if ($bill['status'] !== 'verified' && !$isMgmtUser) {
            setError('Sequential Approval Required: Project Manager / Department Head must verify this Contractor Bill before Head of Purchase can approve.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $note = trim($_POST['ph_note'] ?? '');

        // If Management overrides PM verification during Purchase Head step, mark verified too
        if ($bill['status'] !== 'verified' && $isMgmtUser) {
            $this->billModel->updateStatus($id, [
                'status'            => 'verified',
                'verified_by'       => $this->userId,
                'verified_at'       => date('Y-m-d H:i:s'),
                'verification_note' => ($note ?: 'Approved') . ' (Management Override)',
            ]);
        }

        if ($this->billModel->approvePurchaseHead($id, $this->userId, $note)) {
            $phNoteFull = "Approved by Head of Purchase. " . ($note ? "Note: {$note}" : "");
            $stmtAr = $this->db->prepare(
                "UPDATE approval_requests
                 SET approve_role = 'qc',
                     status = 'pending',
                     approval_note = ?
                 WHERE source_table = 'contractor_bill_requests' AND source_id = ?"
            );
            if ($stmtAr) {
                $stmtAr->bind_param('si', $phNoteFull, $id);
                $stmtAr->execute();
                $stmtAr->close();
            }
            setSuccess('Contractor Bill approved by Head of Purchase! Forwarded to QC & Internal Audit.');
        } else {
            setError('Failed to approve as Head of Purchase.');
        }

        header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
        exit;
    }

    // =========================================================================
    // APPROVE (QC OR MANAGEMENT)
    // =========================================================================

    public function approve($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $bill = $this->billModel->find($id);
        if (!$bill) {
            setError('Bill not found.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }

        $isQcApproval = isset($_POST['status']) && $_POST['status'] === 'qc_approved';

        if ($isQcApproval) {
            // -- QC APPROVAL PATH --
            if (!$this->canApproveQC()) {
                setError('You are not authorized for QC approval.');
                header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
                exit;
            }

            if (!in_array($bill['status'], ['submitted', 'verified'])) {
                setError('Bill is not ready for QC approval.');
                header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
                exit;
            }

            $isMgmtUser = in_array((int)$this->userRole, [1, 3], true);
            if (($bill['status'] !== 'verified' || empty($bill['purchase_head_approved_by_id'])) && !$isMgmtUser) {
                setError('Sequential Approval Required: PM Verification and Head of Purchase (Role 17) approval must be completed before QC & Internal Audit can approve.');
                header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
                exit;
            }

            $qcNote = trim($_POST['approval_note'] ?? '');
            $approvedAmount = (float)($_POST['approved_amount'] ?? $bill['bill_amount']);
            if ($approvedAmount <= 0) $approvedAmount = (float)$bill['bill_amount'];

            // If Management overrides earlier stages during QC approval, populate them
            if ($isMgmtUser) {
                if ($bill['status'] !== 'verified') {
                    $this->billModel->updateStatus($id, [
                        'status'            => 'verified',
                        'verified_by'       => $this->userId,
                        'verified_at'       => date('Y-m-d H:i:s'),
                        'verification_note' => ($qcNote ?: 'Approved') . ' (Management Override)',
                    ]);
                }
                if (empty($bill['purchase_head_approved_by_id'])) {
                    $this->billModel->approvePurchaseHead($id, $this->userId, ($qcNote ?: 'Approved') . ' (Management Override)');
                }
            }

            if ($this->billModel->updateQCStatus($id, $this->userId, $qcNote, $approvedAmount)) {
                // Forward approval request to Operation Head ('operations')
                $stmtAr = $this->db->prepare("
                    UPDATE approval_requests 
                    SET approve_role = 'operations', 
                        status = 'pending', 
                        request_amount = ?, 
                        approval_note = ?
                    WHERE source_table = 'contractor_bill_requests' AND source_id = ?
                ");
                if ($stmtAr) {
                    $forwardNote = "QC Approved (৳" . number_format($approvedAmount, 2) . ")" . (!empty($qcNote) ? " Note: {$qcNote}" : '');
                    $stmtAr->bind_param('dsi', $approvedAmount, $forwardNote, $id);
                    $stmtAr->execute();
                    $stmtAr->close();
                }
                setSuccess('Bill QC Approved successfully & forwarded to Head of Operation (Role 20).');
            } else {
                setError('Failed to QC approve bill.');
            }
        } else {
            // -- OPERATION HEAD & MANAGEMENT APPROVAL PATH --
            if (!$this->canApproveManagement()) {
                setError('You are not authorized for management approval.');
                header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
                exit;
            }

            if (!in_array($bill['status'], ['qc_approved', 'verified', 'submitted'])) {
                setError('Bill is not in an approvable status.');
                header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
                exit;
            }

            $approvedAmount = (float)($_POST['approved_amount'] ?? $bill['approved_amount'] ?? $bill['bill_amount']);
            if ($approvedAmount <= 0) {
                $approvedAmount = (float)$bill['bill_amount'];
            }
            $managementNote = trim($_POST['management_note'] ?? '');

            $roleNameLc = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
            $isOpHeadUser = (in_array((int)$this->userRole, [20, 32], true) || str_contains($roleNameLc, 'operation head') || str_contains($roleNameLc, 'admin & operation'));
            $isMgmtUser = in_array((int)$this->userRole, [1, 3, 38], true) || in_array($roleNameLc, ['super admin', 'admin', 'management', 'board of director', 'board of directors'], true) || str_contains($roleNameLc, 'director') || str_contains($roleNameLc, 'dceo');

            // Fetch approval request record
            $arCheck = $this->db->query("SELECT id, op_head_status, accounts_head_status FROM approval_requests WHERE source_table IN ('contractor_bill_requests', 'con_contractor_bills') AND source_id = {$id} ORDER BY id DESC LIMIT 1");
            $arRow = $arCheck ? $arCheck->fetch_assoc() : null;
            $opHeadStatus = strtolower($arRow['op_head_status'] ?? 'pending');
            $ahStatus = strtolower($arRow['accounts_head_status'] ?? 'pending');

            // Operation Head (Role 20/32) approves after QC for BOTH <= 10K and > 10K, then forwards to Accounts Head (Role 30)
            if ($isOpHeadUser && !$isMgmtUser) {
                if ($bill['status'] !== 'qc_approved') {
                    setError('Sequential Approval Required: Operation Head can only approve after QC & Internal Audit has verified and approved this bill.');
                    header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
                    exit;
                }

                $arId = (int)($arRow['id'] ?? 0);
                $escNote = $this->db->real_escape_string($managementNote ?: 'Verified and approved by Admin & Operation Head');
                if (!class_exists('ApprovalRequest')) {
                    require_once __DIR__ . '/../../models/ApprovalRequest.php';
                }
                $approvalModel = new \ApprovalRequest($this->db);
                if ($arId > 0) {
                    $approvalModel->approveOperationHead($arId, $this->userId, $escNote);
                } else {
                    $this->db->query("UPDATE approval_requests SET op_head_status = 'approved', op_head_by = {$this->userId}, op_head_at = NOW(), op_head_note = '{$escNote}', approve_role = 'accounts_head' WHERE source_table IN ('contractor_bill_requests', 'con_contractor_bills') AND source_id = {$id}");
                }
                setSuccess("Contractor bill verified & approved by Admin & Operation Head (Role 20). Forwarded to Accounts Head (Role 30) for payment verification.");
                header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
                exit;
            }

            // Management Single-Stage Approval (approve ONLY the current pending stage, do not finalize all)
            $approveMode = $_POST['approve_mode'] ?? 'override';
            if ($isMgmtUser && $approveMode === 'single') {
                $escSingleNote = $this->db->real_escape_string($managementNote ?: 'Approved by Management');
                if ($bill['status'] === 'submitted') {
                    $this->billModel->updateStatus($id, [
                        'status'            => 'verified',
                        'verified_by'       => $this->userId,
                        'verified_at'       => date('Y-m-d H:i:s'),
                        'verification_note' => ($managementNote ?: 'Approved') . ' (On behalf of Dept. Head)',
                    ]);
                    $this->db->query("UPDATE approval_requests SET approve_role = 'purchase_head', status = 'pending', approval_note = '{$escSingleNote} (On behalf of Dept. Head)' WHERE source_table IN ('contractor_bill_requests', 'con_contractor_bills') AND source_id = {$id}");
                    setSuccess('Dept. Head stage approved by Management. Forwarded to Head of Purchase.');
                    header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
                    exit;
                } elseif ($bill['status'] === 'verified' && empty($bill['purchase_head_approved_by_id'])) {
                    $this->billModel->approvePurchaseHead($id, $this->userId, ($managementNote ?: 'Approved') . ' (On behalf of Purchase Head)');
                    $this->db->query("UPDATE approval_requests SET approve_role = 'qc', status = 'pending', approval_note = '{$escSingleNote} (On behalf of Purchase Head)' WHERE source_table IN ('contractor_bill_requests', 'con_contractor_bills') AND source_id = {$id}");
                    setSuccess('Purchase Head stage approved by Management. Forwarded to QC & Internal Audit.');
                    header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
                    exit;
                } elseif ($bill['status'] === 'verified') {
                    $this->billModel->updateQCStatus($id, $this->userId, ($managementNote ?: 'Approved') . ' (On behalf of QC)', $approvedAmount);
                    $this->db->query("UPDATE approval_requests SET approve_role = 'operations', status = 'pending', request_amount = {$approvedAmount}, approval_note = '{$escSingleNote} (On behalf of QC)' WHERE source_table IN ('contractor_bill_requests', 'con_contractor_bills') AND source_id = {$id}");
                    setSuccess('QC & Audit stage approved by Management. Forwarded to Head of Operation.');
                    header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
                    exit;
                } elseif ($bill['status'] === 'qc_approved' && $opHeadStatus !== 'approved') {
                    $this->db->query("UPDATE approval_requests SET op_head_status = 'approved', op_head_by = {$this->userId}, op_head_at = NOW(), op_head_note = '{$escSingleNote} (On behalf of Operation Head)', approve_role = 'accounts_head' WHERE source_table IN ('contractor_bill_requests', 'con_contractor_bills') AND source_id = {$id}");
                    setSuccess('Operation Head stage approved by Management. Forwarded to Accounts Head.');
                    header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
                    exit;
                } elseif ($bill['status'] === 'qc_approved' && $opHeadStatus === 'approved' && $ahStatus !== 'approved') {
                    $this->db->query("UPDATE approval_requests SET accounts_head_status = 'approved', accounts_head_by = {$this->userId}, accounts_head_at = NOW(), accounts_head_note = '{$escSingleNote} (On behalf of Accounts Head)', approve_role = 'management' WHERE source_table IN ('contractor_bill_requests', 'con_contractor_bills') AND source_id = {$id}");
                    setSuccess('Accounts Head stage approved by Management. Ready for Final Management Approval.');
                    header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
                    exit;
                }
            }

            if ($this->billModel->approve($id, $this->userId, $approvedAmount, $managementNote)) {
                $escMgtNote = $this->db->real_escape_string($managementNote ?: 'Approved by Management');

                if ($isMgmtUser) {
                    // Auto-populate any bypassed earlier stages on contractor_bill_requests so responsibility is attributed to this Management person
                    $this->db->query("
                        UPDATE contractor_bill_requests
                        SET verified_by = COALESCE(verified_by, {$this->userId}),
                            verified_at = COALESCE(verified_at, NOW()),
                            verification_note = COALESCE(verification_note, '{$escMgtNote} (Management Override)'),
                            purchase_head_approved_by_id = COALESCE(purchase_head_approved_by_id, {$this->userId}),
                            purchase_head_approved_at = COALESCE(purchase_head_approved_at, NOW()),
                            purchase_head_note = COALESCE(purchase_head_note, '{$escMgtNote} (Management Override)'),
                            qc_approved_by_id = COALESCE(qc_approved_by_id, {$this->userId}),
                            qc_approved_at = COALESCE(qc_approved_at, NOW()),
                            qc_note = COALESCE(qc_note, '{$escMgtNote} (Management Override)')
                        WHERE id = " . (int)$id . "
                    ");

                    // Attribute bypassed Operation Head and Accounts Head steps to this Management user so disbursement is immediately unlocked
                    $this->db->query("
                        UPDATE approval_requests
                        SET status = 'approved',
                            approve_role = 'accounts',
                            approved_amount = {$approvedAmount},
                            approved_by = {$this->userId},
                            approved_at = NOW(),
                            approval_note = '{$escMgtNote}',
                            op_head_status = 'approved',
                            op_head_by = COALESCE(op_head_by, {$this->userId}),
                            op_head_at = COALESCE(op_head_at, NOW()),
                            op_head_note = COALESCE(op_head_note, '{$escMgtNote} (Management Override)'),
                            accounts_head_status = 'approved',
                            accounts_head_by = COALESCE(accounts_head_by, {$this->userId}),
                            accounts_head_at = COALESCE(accounts_head_at, NOW()),
                            accounts_head_note = COALESCE(accounts_head_note, '{$escMgtNote} (Management Override)')
                        WHERE source_table IN ('contractor_bill_requests', 'con_contractor_bills') AND source_id = " . (int)$id . "
                    ");
                } else {
                    $this->syncApprovalRequest($id, 'approved', $approvedAmount);
                    $nextRoleCb = ($ahStatus === 'approved') ? 'accounts' : 'accounts_head';
                    $this->db->query("UPDATE approval_requests SET approve_role = '{$nextRoleCb}', status = 'approved', op_head_status = 'approved' WHERE source_table IN ('contractor_bill_requests', 'con_contractor_bills') AND source_id = " . (int)$id);
                }

                // AUTO-GENERATE ACCRUAL JOURNAL
                try {
                    require_once __DIR__ . '/../../services/JournalIntegrationService.php';
                    $journalService = new \Services\JournalIntegrationService($this->db);
                    $journalService->generateContractorAccrual($id, $this->userId);
                } catch (\Throwable $e) {
                    error_log("Failed to auto-generate contractor accrual journal: " . $e->getMessage());
                }

                // Send email notification on approval
                try {
                    $updatedBill = $this->billModel->find($id);
                    if ($updatedBill) {
                        // Fetch contractor and project names for the email
                        $operator = $this->operatorModel->find($bill['operator_id'] ?? 0);
                        $project  = $this->db->query("SELECT name FROM projects WHERE id = " . (int)($bill['project_id'] ?? 0))->fetch_assoc();
                        $updatedBill['contractor_name'] = $operator['name'] ?? '';
                        $updatedBill['project_name']    = $project['name'] ?? '';

                        $this->mailService->sendContractorBillOnApproval(
                            $updatedBill,
                            'approved',
                            $this->userId
                        );
                    }
                } catch (\Exception $e) {
                    error_log("Contractor Bill email (approval) failed: " . $e->getMessage());
                }

                setSuccess('Bill approved with amount ৳' . number_format($approvedAmount, 2));
            } else {
                setError('Failed to approve or bill already processed.');
            }
        }

        header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
        exit;
    }

    // =========================================================================
    // REJECT — Management (roles 1,3)
    // =========================================================================

    public function reject($id)
    {
        if (!$this->canApprovePM() && !$this->canApprovePurchaseHead() && !$this->canApproveQC() && !$this->canApproveManagement()) {
            setError('You are not authorized to reject bills.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $reason = trim($_POST['rejection_reason'] ?? $_POST['remarks'] ?? '');
        if (empty($reason)) {
            setError('Rejection remarks/reason is required.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        if ($this->billModel->reject($id, $this->userId, $reason)) {
            $this->syncApprovalRequest($id, 'rejected', null, $reason);
            
            // Send email notification on rejection
            try {
                $rejectedBill = $this->billModel->find($id);
                if ($rejectedBill) {
                    $operator = $this->operatorModel->find($rejectedBill['operator_id'] ?? 0);
                    $project  = $this->db->query("SELECT name FROM projects WHERE id = " . (int)($rejectedBill['project_id'] ?? 0))->fetch_assoc();
                    $rejectedBill['contractor_name']  = $operator['name'] ?? '';
                    $rejectedBill['project_name']     = $project['name'] ?? '';
                    $rejectedBill['rejection_reason'] = $reason;

                    $this->mailService->sendContractorBillOnRejection(
                        $rejectedBill,
                        $this->userId
                    );
                }
            } catch (\Exception $e) {
                error_log("Contractor Bill email (rejection) failed: " . $e->getMessage());
            }

            setSuccess('Contractor bill has been rejected.');
        } else {
            setError('Failed to reject bill.');
        }
        header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
        exit;
    }

    // =========================================================================
    // ADD PAYMENT — Accounts (roles 1,3,6)
    // =========================================================================

    public function addPayment($id)
    {
        if (!$this->canProcessPayment()) {
            setError('You are not authorized to process payments.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $bill = $this->billModel->find($id);
        if (!$bill || !in_array($bill['status'], ['approved', 'partially_paid'])) {
            setError('Bill is not in a payable status.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        // STRICT GUARD: Must be approved by Accounts Head (Role 30 - Md. Mamunoor Rashid) before payment disbursement
        require_once __DIR__ . '/../../models/ApprovalRequest.php';
        $ahCheck = \ApprovalRequest::getAccountsHeadApprovalInfo($this->db, 'contractor_bill_requests', $id);
        if ($ahCheck['has_request'] && !$ahCheck['is_accounts_head_approved']) {
            setError('Payment cannot be processed! Accounts Head approval (Md. Mamunoor Rashid [1309]) is required before payment disbursement.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        // Handle file upload (mandatory)
        $proofPaths = [];
        if (!empty($_FILES['disbursement_proof']['name'][0])) {
            foreach ($_FILES['disbursement_proof']['tmp_name'] as $idx => $tmpName) {
                if ($_FILES['disbursement_proof']['error'][$idx] !== UPLOAD_ERR_OK) continue;
                $file = [
                    'name'     => $_FILES['disbursement_proof']['name'][$idx],
                    'tmp_name' => $tmpName,
                    'size'     => $_FILES['disbursement_proof']['size'][$idx],
                    'error'    => $_FILES['disbursement_proof']['error'][$idx],
                ];
                $res = $this->handleFileUpload($file);
                if ($res['success']) {
                    $proofPaths[] = $res['file_path'];
                } else {
                    setError($res['message']);
                    header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
                    exit;
                }
            }
        }

        if (empty($proofPaths)) {
            setError('Disbursement proof is required.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount <= 0) {
            setError('Payment amount must be greater than zero.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $totalPaidSoFar = $this->txnModel->getTotalPaidForSource('contractor_bill', $id);
        $payableAmt     = (float)($bill['approved_amount'] ?? $bill['bill_amount']);
        $remainingDue   = max(0, $payableAmt - $totalPaidSoFar);

        if ($amount > $remainingDue + 0.01) {
            setError("Payment (৳" . number_format($amount, 2) . ") exceeds remaining due (৳" . number_format($remainingDue, 2) . ").");
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $paymentType = ($amount < $remainingDue - 0.01) ? 'Partial' : 'Full';

        $data = [
            'ref_type'           => 'contractor_bill',
            'ref_id'             => (int)$id,
            'project_id'         => (int)($bill['project_id'] ?? 0),
            'amount'             => $amount,
            'payment_type'       => $paymentType,
            'payment_method'     => $_POST['payment_method'] ?? 'Cash',
            'payment_date'       => $_POST['payment_date'] ?? date('Y-m-d'),
            'reference_no'       => trim($_POST['reference_no'] ?? ''),
            'notes'              => trim($_POST['notes'] ?? ''),
            'disbursement_proof' => json_encode($proofPaths),
            'processed_by'       => $this->userId,
        ];

        $txnId = $this->txnModel->create($data);

        if ($txnId) {
            setSuccess('Payment of ৳' . number_format($amount, 2) . ' recorded! Transaction #' . $txnId);
        } else {
            setError('Failed to process payment.');
        }

        header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
        exit;
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function getApprovalRequest($billId)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM approval_requests
             WHERE source_table = 'contractor_bill_requests' AND source_id = ?
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->bind_param('i', $billId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function syncApprovalRequest($billId, $newStatus, $approvedAmount = null, $note = null)
    {
        $arStatus = ($newStatus === 'approved') ? 'approved' : 'rejected';
        $approveRole = ($newStatus === 'approved') ? 'accounts' : 'rejected';

        $stmt = $this->db->prepare(
            "UPDATE approval_requests
             SET status = ?, approve_role = ?, approved_by = ?, approved_amount = COALESCE(?, approved_amount, request_amount), approval_note = COALESCE(?, approval_note), approved_at = NOW()
             WHERE source_table = 'contractor_bill_requests' AND source_id = ?"
        );
        if ($stmt) {
            $stmt->bind_param('ssidsi', $arStatus, $approveRole, $this->userId, $approvedAmount, $note, $billId);
            $stmt->execute();
            $stmt->close();
        }
    }

    private function handleFileUpload($file)
    {
        $allowedTypes = ['doc', 'docx', 'pdf', 'jpg', 'jpeg', 'png'];
        $maxSize      = 5 * 1024 * 1024;

        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedTypes)) {
            return ['success' => false, 'message' => "File '{$file['name']}': only DOC, DOCX, PDF, JPG, PNG allowed."];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => "File '{$file['name']}' exceeds 5MB limit."];
        }

        $newFileName = 'con_bill_payment_' . time() . '_' . uniqid() . '.' . $fileExt;
        $uploadPath  = 'uploads/construction-payments/' . $newFileName;
        $fullPath    = __DIR__ . '/../../../public/' . $uploadPath;

        $dir = dirname($fullPath);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            return ['success' => true, 'file_path' => $uploadPath];
        }

        return ['success' => false, 'message' => "Failed to upload file '{$file['name']}'."];
    }

    // =========================================================================
    // HELPER — Get projects filtered by role
    // =========================================================================
    private function getProjects()
    {
        $uid = $this->userId;
        $rid = $this->userRole;

        // Admin, Director, PM Head, Purchase Roles (17, 23, 31) → see ALL projects
        if (in_array($rid, [1, 3, 33, 22, 17, 23, 31])) {
            $filter = "1=1";
        } else {
            // All other roles → only their assigned projects
            $filter = "(p.pm_id = $uid
                     OR p.site_engineer_id    = $uid
                     OR p.landman_id          = $uid
                     OR p.struct_engineer_id  = $uid
                     OR p.operation_head_id   = $uid
                     OR p.draftsman_id        = $uid
                     OR p.architect_id        = $uid
                     OR p.estimator_id        = $uid
                     OR p.qc_id              = $uid
                     OR p.operator_id        = $uid
                     OR p.created_by         = $uid)";
        }

        $r = $this->db->query(
            "SELECT p.id, p.name 
             FROM projects p
             WHERE p.deleted_at IS NULL AND {$filter}
             ORDER BY p.name ASC"
        );
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    private function calculateWorkOrderGain(int $weeklyProgressId, int $workOrderId): float
    {
        // Fetch weekly progress details
        $stmt = $this->db->prepare("
            SELECT target_id, week_number 
            FROM phase_weekly_progress 
            WHERE id = ?
        ");
        $stmt->bind_param('i', $weeklyProgressId);
        $stmt->execute();
        $wp = $stmt->get_result()->fetch_assoc();
        if (!$wp) return 0.0;

        // Strategy:
        // 1. Try to match via pwpi.work_order_id directly (if items are linked)
        // 2. Fall back: match via Work Order Items' phase_item_id → monthly target items' work_item_name

        $query = $this->db->prepare("
            SELECT 
                pmti.id AS pmti_id,
                pmti.target_mode,
                pmti.monthly_target_qty,
                pmti.monthly_target_pct,
                SUM(pwpi_prev.achieved_qty) AS cumulative_achieved_qty,
                SUM(pwpi_prev.achieved_pct) AS cumulative_achieved_pct
            FROM phase_weekly_progress_items pwpi
            INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
            INNER JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
            INNER JOIN phase_weekly_progress pwp_prev ON pwp_prev.target_id = pwp.target_id 
                AND pwp_prev.week_number <= pwp.week_number
            INNER JOIN phase_weekly_progress_items pwpi_prev ON pwpi_prev.weekly_progress_id = pwp_prev.id 
                AND pwpi_prev.target_item_id = pwpi.target_item_id
            WHERE pwpi.weekly_progress_id = ?
              AND (
                  pwpi.work_order_id = ?
                  OR pmti.work_order_id = ?
                  OR EXISTS (
                      SELECT 1 FROM con_work_order_items woi
                      INNER JOIN project_phase_items ppi ON ppi.id = woi.phase_item_id
                      WHERE woi.work_order_id = ?
                        AND (
                            ppi.item_name = pmti.work_item_name COLLATE utf8mb4_general_ci
                            OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, ' - %')
                            OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, '%')
                        )
                  )
              )
            GROUP BY pmti.id
        ");
        $query->bind_param('iiii', $weeklyProgressId, $workOrderId, $workOrderId, $workOrderId);
        $query->execute();
        $items = $query->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($items)) return 0.0;

        $totalPctSum = 0.0;
        $count = 0;

        foreach ($items as $item) {
            $mode = $item['target_mode'];
            $itemPct = 0.0;
            if ($mode === 'pct') {
                $targetPct = (float)$item['monthly_target_pct'];
                $achievedPct = (float)$item['cumulative_achieved_pct'];
                if ($targetPct > 0) {
                    $itemPct = ($achievedPct / $targetPct) * 100;
                }
            } else {
                $targetQty = (float)$item['monthly_target_qty'];
                $achievedQty = (float)$item['cumulative_achieved_qty'];
                if ($targetQty > 0) {
                    $itemPct = ($achievedQty / $targetQty) * 100;
                }
            }
            $totalPctSum += min(100.0, $itemPct);
            $count++;
        }

        return $count > 0 ? round($totalPctSum / $count, 2) : 0.0;
    }

    private function findTarget($targetId, $workOrderId = null)
    {
        $stmt = $this->db->prepare("
            SELECT pwp.id,
                   pwp.overall_progress_pct AS gain_percentage,
                   100.00 AS target_percentage,
                   pmt.target_year AS year,
                   pwp.week_number,
                   pp.phase_name AS target_title
            FROM phase_weekly_progress pwp
            INNER JOIN phase_monthly_targets pmt ON pmt.id = pwp.target_id
            INNER JOIN project_phases pp ON pp.id = pwp.phase_id
            WHERE pwp.id = ?
        ");
        $stmt->bind_param('i', $targetId);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();
        if ($target && $workOrderId) {
            $target['gain_percentage'] = $this->calculateWorkOrderGain((int)$targetId, (int)$workOrderId);
        }
        return $target;
    }

    private function getWorkOrderTargetDetails(int $weeklyProgressId, int $workOrderId): array
    {
        $query = $this->db->prepare("
            SELECT 
                pmti.work_item_name,
                pmti.target_mode,
                pmti.monthly_target_qty,
                pmti.monthly_target_pct,
                pwpi.planned_qty,
                pwpi.planned_pct,
                pwpi.achieved_qty,
                pwpi.achieved_pct,
                pwpi.percent_complete,
                SUM(pwpi_prev.achieved_qty) AS cumulative_achieved_qty,
                SUM(pwpi_prev.achieved_pct) AS cumulative_achieved_pct
            FROM phase_weekly_progress_items pwpi
            INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
            INNER JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
            INNER JOIN phase_weekly_progress pwp_prev ON pwp_prev.target_id = pwp.target_id 
                AND pwp_prev.week_number <= pwp.week_number
            INNER JOIN phase_weekly_progress_items pwpi_prev ON pwpi_prev.weekly_progress_id = pwp_prev.id 
                AND pwpi_prev.target_item_id = pwpi.target_item_id
            WHERE pwpi.weekly_progress_id = ?
              AND (
                  pwpi.work_order_id = ?
                  OR pmti.work_order_id = ?
                  OR EXISTS (
                      SELECT 1 FROM con_work_order_items woi
                      INNER JOIN project_phase_items ppi ON ppi.id = woi.phase_item_id
                      WHERE woi.work_order_id = ?
                        AND (
                            ppi.item_name = pmti.work_item_name COLLATE utf8mb4_general_ci
                            OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, ' - %')
                            OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, '%')
                       )
                  ) 
              )
            GROUP BY pmti.id
        ");
        $query->bind_param('iiii', $weeklyProgressId, $workOrderId, $workOrderId, $workOrderId);
        $query->execute();
        $items = $query->get_result()->fetch_all(MYSQLI_ASSOC);

        $details = [];
        foreach ($items as $item) {
            $mode = $item['target_mode'];
            $cumPct = 0.0;
            if ($mode === 'pct') {
                $targetPct = (float)$item['monthly_target_pct'];
                $achievedPct = (float)$item['cumulative_achieved_pct'];
                if ($targetPct > 0) {
                    $cumPct = ($achievedPct / $targetPct) * 100;
                }
            } else {
                $targetQty = (float)$item['monthly_target_qty'];
                $achievedQty = (float)$item['cumulative_achieved_qty'];
                if ($targetQty > 0) {
                    $cumPct = ($achievedQty / $targetQty) * 100;
                }
            }
            $details[] = [
                'item_name' => $item['work_item_name'],
                'target_mode' => $item['target_mode'],
                'planned' => $mode === 'pct' ? (float)$item['planned_pct'] : (float)$item['planned_qty'],
                'achieved' => $mode === 'pct' ? (float)$item['achieved_pct'] : (float)$item['achieved_qty'],
                'weekly_pct' => (float)$item['percent_complete'],
                'cumulative_pct' => min(100.0, round($cumPct, 2)),
            ];
        }
        return $details;
    }

    public function ajaxGetTargetsByProject()
    {
        header('Content-Type: application/json');
        $projectId = (int)($_GET['project_id'] ?? 0);
        $workOrderId = (int)($_GET['work_order_id'] ?? 0);
        if (!$projectId) {
            echo json_encode(['targets' => []]);
            exit;
        }

        $stmt = $this->db->prepare("
            SELECT pwp.id AS id,
                   pmt.target_year AS year,
                   pwp.week_number AS week_number,
                   pp.phase_name AS target_title,
                   pwp.overall_progress_pct AS gain_percentage
            FROM phase_weekly_progress pwp
            INNER JOIN phase_monthly_targets pmt ON pmt.id = pwp.target_id
            INNER JOIN project_phases pp ON pp.id = pwp.phase_id
            WHERE pwp.project_id = ? AND pwp.entry_status = 'Approved'
            ORDER BY pmt.target_year DESC, pmt.target_month_number DESC, pwp.week_number DESC
        ");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $targets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if ($workOrderId > 0) {
            foreach ($targets as &$t) {
                $t['gain_percentage'] = $this->calculateWorkOrderGain((int)$t['id'], $workOrderId);
                $t['items_detail'] = $this->getWorkOrderTargetDetails((int)$t['id'], $workOrderId);
            }
        } else {
            foreach ($targets as &$t) {
                $t['items_detail'] = [];
            }
        }

        echo json_encode(['targets' => $targets]);
        exit;
    }

    // =========================================================================
    // SETTLEMENT — Site engineer accountability after payment
    // =========================================================================

    public function submitSettlement($billId)
    {
        $billId = (int)$billId;
        $bill   = $this->billModel->findWithRelations($billId);
        if (!$bill) {
            setError('Bill not found.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }

        // Validate required fields
        $paidAmount  = (float)($_POST['paid_amount'] ?? 0);
        $paidDate    = trim($_POST['paid_date'] ?? '');
        $receivedAmt = (float)($_POST['received_amount'] ?? 0);
        $labourCount = (int)($_POST['labour_count'] ?? 0);
        $method      = trim($_POST['payment_method'] ?? 'Cash');
        $refNo       = trim($_POST['reference_no'] ?? '');
        $workNote    = trim($_POST['work_note'] ?? '');

        if ($paidAmount <= 0 || empty($paidDate)) {
            setError('Paid amount and date are required.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$billId}");
            exit;
        }

        // Handle multiple proof image uploads
        $proofPaths = [];
        $uploadDir  = __DIR__ . '/../../../public/uploads/contractor-bill-settlements/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        if (!empty($_FILES['proof_images']['name'][0])) {
            foreach ($_FILES['proof_images']['name'] as $idx => $fname) {
                if (empty($fname)) continue;
                $ext      = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
                $allowed  = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
                if (!in_array($ext, $allowed)) continue;
                if ($_FILES['proof_images']['size'][$idx] > 5 * 1024 * 1024) continue;
                $newName = time() . '_' . $idx . '_' . basename($fname);
                if (move_uploaded_file($_FILES['proof_images']['tmp_name'][$idx], $uploadDir . $newName)) {
                    $proofPaths[] = 'uploads/contractor-bill-settlements/' . $newName;
                }
            }
        }

        $settId = $this->settlementModel->create([
            'bill_id'        => $billId,
            'project_id'     => $bill['project_id'],
            'contractor_id'  => $bill['operator_id'],
            'submitted_by'   => $this->userId,
            'paid_amount'    => $paidAmount,
            'paid_date'      => $paidDate,
            'payment_method' => $method,
            'reference_no'   => $refNo,
            'labour_count'   => $labourCount,
            'work_note'      => $workNote,
            'proof_images'   => $proofPaths,
            'received_amount'=> $receivedAmt,
        ]);

        if ($settId) {
            setSuccess('Settlement submitted successfully! Accounts will verify it.');
        } else {
            setError('Failed to submit settlement. Please try again.');
        }
        header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$billId}");
        exit;
    }

    public function markBalanceReturned($settlementId)
    {
        $settlementId = (int)$settlementId;
        $sett = $this->settlementModel->find($settlementId);
        if (!$sett) {
            setError('Settlement not found.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }
        $billId = (int)$sett['bill_id'];

        if ($this->settlementModel->markBalanceReturned($settlementId)) {
            setSuccess('Balance marked as returned to accounts.');
        } else {
            setError('Could not update. Already marked or not found.');
        }
        header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$billId}");
        exit;
    }

    public function verifySettlement($settlementId)
    {
        $settlementId = (int)$settlementId;
        $sett = $this->settlementModel->find($settlementId);
        if (!$sett || !$this->canProcessPayment()) {
            setError('Unauthorized or settlement not found.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }
        $billId = (int)$sett['bill_id'];
        $note   = trim($_POST['accounts_note'] ?? '');

        if ($this->settlementModel->verify($settlementId, $this->userId, $note)) {
            setSuccess('Settlement verified successfully.');
        } else {
            setError('Failed to verify settlement.');
        }
        header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$billId}");
        exit;
    }
}