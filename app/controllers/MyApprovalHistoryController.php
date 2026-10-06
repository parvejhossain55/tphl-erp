<?php
class MyApprovalHistoryController extends Controller
{
    private $db;
    private $userId;
    private $isAccounts;

    public function __construct($db)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user']['id'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit();
        }
        $this->db = $db;
        $this->userId = (int)$_SESSION['user']['id'];

        $roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $this->isAccounts = strpos($roleName, 'account') !== false || in_array($_SESSION['user']['role_id'] ?? 0, [30, 3]);
    }

    public function index()
    {
        $limit = 20;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $limit;
        
        $searchModule = isset($_GET['module']) ? trim($_GET['module']) : '';
        $searchReqNo = isset($_GET['request_no']) ? trim($_GET['request_no']) : '';
        $searchDept = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

        $queries = [];
        $uid = $this->userId;
        
        $addQuery = function($module, $table, $idCol, $reqBy, $titleCol, $amountCol, $roles) use (&$queries, $uid, $searchModule, $searchReqNo, $searchDept) {
            if ($searchModule && $searchModule !== 'all' && stripos($module, $searchModule) === false) return;
            
            $whereParts = [];
            $casesPrevId = "CASE ";
            $casesPrevAt = "CASE ";
            $casesMyAt = "CASE ";
            $casesMyRole = "CASE ";
            
            // Build chronologically to find previous approver
            // Assuming $roles is ordered from final approver to first approver
            foreach ($roles as $roleName => $cols) {
                $whereParts[] = "{$cols['by']} = $uid";
                
                // For 'my_action_time' and 'my_role', the first matching role (highest in hierarchy) where user is approver
                $casesMyAt .= "WHEN {$cols['by']} = $uid THEN IFNULL({$cols['at']}, t.updated_at) ";
                $casesMyRole .= "WHEN {$cols['by']} = $uid THEN '$roleName' ";
            }
            $casesMyAt .= "ELSE t.updated_at END";
            $casesMyRole .= "ELSE 'Unknown' END";

            if (empty($whereParts)) return;
            $whereClause = implode(' OR ', $whereParts);
            
            // Subquery to get previous approver (this is complex in pure SQL, we'll extract it in PHP instead)
            // We select all role columns to determine prev approver in PHP
            
            $selectCols = ["'$module' AS module_name", "t.$idCol AS request_id", "t.$reqBy AS requested_by", "t.$titleCol AS request_title", "t.$amountCol AS approved_amount", "t.status", "t.created_at"];
            
            foreach ($roles as $roleName => $cols) {
                $selectCols[] = "{$cols['by']} AS `{$roleName}_by`";
                $selectCols[] = "{$cols['at']} AS `{$roleName}_at`";
            }
            $selectColsStr = implode(", ", $selectCols);

            $queryStr = "SELECT $selectColsStr, $casesMyAt AS my_action_time, $casesMyRole AS my_role 
                         FROM $table t 
                         LEFT JOIN approval_requests ar ON ar.source_table = '$table' AND ar.source_id = t.$idCol 
                         WHERE ($whereClause)";
            
            if ($searchReqNo) {
                // If the user typed something like "#REQ-03337", extract the number, else just use the input
                $numericReq = preg_replace('/[^0-9]/', '', $searchReqNo);
                if ($numericReq) {
                    $queryStr .= " AND t.$idCol = $numericReq";
                } else {
                    $queryStr .= " AND t.$idCol LIKE '%$searchReqNo%'";
                }
            }
            if ($searchDept > 0) {
                $queryStr .= " AND t.$reqBy IN (SELECT user_id FROM employees WHERE department_id = $searchDept)";
            }
            
            $queries[] = $queryStr;
        };

        // Define modules and their role columns (ordered logically from last to first)
        $addQuery('Fund Requisition', 'fund_requisitions', 'id', 'requested_by', 'purpose', 'approved_amount', [
            'Accounts' => ['by' => 't.paid_by', 'at' => 't.paid_at'],
            'Accounts Head' => ['by' => 'ar.accounts_head_by', 'at' => 'ar.accounts_head_at'],
            'Management' => ['by' => 't.approved_by', 'at' => 't.approved_at'],
            'QC' => ['by' => 't.qc_approved_by', 'at' => 't.qc_approved_at'],
            'Dept Head' => ['by' => 't.dept_head_approved_by', 'at' => 't.dept_head_approved_at']
        ]);

        $addQuery('Labor Requisition', 'con_labor_requisitions', 'id', 'created_by', "CONCAT('Labor Requisition #', t.requisition_no)", 'approved_amount', [
            'Accounts' => ['by' => 't.paid_by', 'at' => 't.updated_at'], // paid_at not clearly defined, fallback to updated_at
            'Accounts Head' => ['by' => 'ar.accounts_head_by', 'at' => 'ar.accounts_head_at'],
            'Management' => ['by' => 't.management_approved_by', 'at' => 't.management_approved_at'],
            'QC' => ['by' => 't.qc_approved_by', 'at' => 't.qc_approved_at'],
            'PM' => ['by' => 't.pm_approved_by', 'at' => 't.pm_approved_at']
        ]);
        
        $addQuery('Vendor Payment', 'vendor_payment_requests', 'id', 'created_by', "CONCAT('Vendor Payment #', t.id)", 'approved_amount', [
            'Accounts Head' => ['by' => 'ar.accounts_head_by', 'at' => 'ar.accounts_head_at'],
            'Management' => ['by' => 't.approved_by', 'at' => 't.updated_at'],
            'QC' => ['by' => 't.qc_approved_by_id', 'at' => 't.qc_approved_at']
        ]);
        
        $addQuery('Project Other Cost', 'project_other_costs', 'id', 'created_by_id', 'purpose', 'approved_amount', [
            'Accounts' => ['by' => 't.payment_by_id', 'at' => 't.payment_at'],
            'Accounts Head' => ['by' => 'ar.accounts_head_by', 'at' => 'ar.accounts_head_at'],
            'Management' => ['by' => 't.management_apporved_by_id', 'at' => 't.management_apporved_at'],
            'QC' => ['by' => 't.qc_audit_approved_id', 'at' => 't.qc_audit_approved_at'],
            'Dept Head' => ['by' => 't.dept_head_approved_by', 'at' => 't.dept_head_approved_at']
        ]);
        
        $addQuery('Contractor Bill', 'contractor_bill_requests', 'id', 'created_by', "CONCAT('Contractor Bill #', t.id)", 'approved_amount', [
            'Accounts Head' => ['by' => 'ar.accounts_head_by', 'at' => 'ar.accounts_head_at'],
            'Management' => ['by' => 't.approved_by', 'at' => 't.updated_at'],
            'QC' => ['by' => 't.qc_approved_by_id', 'at' => 't.qc_approved_at'],
            'PM' => ['by' => 't.verified_by', 'at' => 't.verified_at']
        ]);
        
        $addQuery('Project Extra Asset', 'project_extra_assets', 'id', 'requested_by', "CONCAT('Extra Asset #', t.id)", 'paid_amount', [
            'Accounts' => ['by' => 't.paid_by', 'at' => 't.paid_at'],
            'Accounts Head' => ['by' => 'ar.accounts_head_by', 'at' => 'ar.accounts_head_at'],
            'Management' => ['by' => 't.mgmt_by', 'at' => 't.mgmt_at'],
            'QC' => ['by' => 't.qc_audit_by', 'at' => 't.qc_audit_at'],
            'Operations' => ['by' => 't.operations_by', 'at' => 't.operations_at'],
            'Purchase Head' => ['by' => 't.purchase_head_by', 'at' => 't.purchase_head_at'],
            'Dept Head' => ['by' => 't.dept_head_by', 'at' => 't.dept_head_at']
        ]);
        
        $addQuery('Cashout Request', 'cashout_requests', 'id', 'requested_by', 'purpose_paid_to', 'approved_amount', [
            'Accounts Head' => ['by' => 'ar.accounts_head_by', 'at' => 'ar.accounts_head_at'],
            'Management' => ['by' => 't.approved_by', 'at' => 't.approved_at'],
            'QC' => ['by' => 't.qc_approved_by_id', 'at' => 't.qc_approved_at']
        ]);
        
        $addQuery('Expense Adjustment', 'expense_adjustments', 'id', 'approved_by', 'title', 'approved_amount', [
            'Accounts Head' => ['by' => 'ar.accounts_head_by', 'at' => 'ar.accounts_head_at'],
            'Management' => ['by' => 't.approved_by', 'at' => 't.approved_at'],
            'QC' => ['by' => 't.qc_approved_by', 'at' => 't.qc_approved_at']
        ]);

        $addQuery('Land Fund Requisition', 'land_fund_requisitions', 'id', 'requested_by', 'purpose', 'requested_amount', [
            'Accounts' => ['by' => 't.accounts_by', 'at' => 't.accounts_paid_at'],
            'Accounts Head' => ['by' => 'ar.accounts_head_by', 'at' => 'ar.accounts_head_at'],
            'Management' => ['by' => 't.mgmt_by', 'at' => 't.mgmt_approved_at']
        ]);
        
        $fullResults = [];
        foreach ($queries as $q) {
            $res = $this->db->query($q);
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $fullResults[] = $row;
                }
            }
        }

        // Sort by my_action_time DESC
        usort($fullResults, function($a, $b) {
            return strtotime($b['my_action_time']) - strtotime($a['my_action_time']);
        });

        // Pagination
        $totalItems = count($fullResults);
        $totalPages = ceil($totalItems / $limit);
        $paginatedResults = array_slice($fullResults, $offset, $limit);

        // Enhance data (users, prev approvers)
        $userIds = [];
        foreach ($paginatedResults as $r) {
            $userIds[] = (int)$r['requested_by'];
            foreach ($r as $key => $val) {
                if (strpos($key, '_by') !== false && $val) {
                    $userIds[] = (int)$val;
                }
            }
        }
        $userIds = array_unique(array_filter($userIds));
        $users = [];
        if (!empty($userIds)) {
            $idsStr = implode(',', $userIds);
            $uRes = $this->db->query("SELECT id, full_name FROM users WHERE id IN ($idsStr)");
            if ($uRes) {
                while($u = $uRes->fetch_assoc()){
                    $users[$u['id']] = $u['full_name'];
                }
            }
        }

        $enhancedResults = [];
        foreach ($paginatedResults as $r) {
            $r['requested_by_name'] = $users[$r['requested_by']] ?? 'Unknown';
            
            // Find previous approver
            // Order of roles is from Last to First. E.g. Accounts -> Management -> QC -> Dept Head
            // Find my position, then the immediate next non-empty one is the previous approver
            $myRole = $r['my_role'];
            $foundMe = false;
            $prevApproverId = null;
            $prevApprovedAt = null;
            $prevRole = null;
            
            foreach ($r as $key => $val) {
                if (strpos($key, '_by') !== false && strpos($key, 'requested_by') === false) {
                    $roleKey = str_replace('_by', '', $key); // e.g. Management, QC
                    if ($roleKey == $myRole) {
                        $foundMe = true;
                        continue;
                    }
                    if ($foundMe && !empty($val)) {
                        $prevApproverId = $val;
                        $prevApprovedAt = $r[$roleKey . '_at'] ?? null;
                        $prevRole = $roleKey;
                        break;
                    }
                }
            }
            
            $r['prev_approver_name'] = $prevApproverId ? ($users[$prevApproverId] ?? 'Unknown') : 'None';
            $r['prev_approved_at'] = $prevApprovedAt;
            $r['prev_role'] = $prevRole;
            
            $enhancedResults[] = $r;
        }

        $deptRes = $this->db->query("SELECT id, name FROM departments ORDER BY name ASC");
        $departments = [];
        if ($deptRes) {
            while($d = $deptRes->fetch_assoc()) {
                $departments[] = $d;
            }
        }

        $data = [
            'history' => $enhancedResults,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'searchModule' => $searchModule,
            'searchReqNo' => $searchReqNo,
            'searchDept' => $searchDept,
            'departments' => $departments,
            'isAccounts' => $this->isAccounts
        ];

        $this->view('approvals/my-approval-history', $data);
    }
}
