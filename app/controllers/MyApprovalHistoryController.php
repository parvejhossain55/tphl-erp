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
        $perPageParam = isset($_GET['per_page']) ? trim($_GET['per_page']) : (isset($_GET['limit']) ? trim($_GET['limit']) : '20');
        $isViewAll = in_array(strtolower($perPageParam), ['all', '0', '-1']);
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        
        $searchModule = isset($_GET['module']) ? trim($_GET['module']) : '';
        $searchReqNo = isset($_GET['request_no']) ? trim($_GET['request_no']) : '';
        $searchDept = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

        $queries = [];
        $uid = $this->userId;
        
        $addQuery = function($module, $table, $idCol, $reqBy, $titleExpr, $reqAmtExpr, $qcAmtExpr, $mgmtAmtExpr, $paidAmtExpr, $statusExpr, $roles) use (&$queries, $uid, $searchModule, $searchReqNo, $searchDept) {
            if ($searchModule && $searchModule !== 'all' && stripos($module, $searchModule) === false) return;
            
            $whereParts = [];
            $casesMyAt = "CASE ";
            $casesMyRole = "CASE ";
            
            // Roles ordered chronologically from final approver to first approver
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
            
            $selectCols = [
                "'$module' AS module_name",
                "t.$idCol AS request_id",
                "$reqBy AS requested_by",
                "$titleExpr AS request_title",
                "$reqAmtExpr AS req_amount",
                "$qcAmtExpr AS qc_amount",
                "$mgmtAmtExpr AS mgmt_amount",
                "$paidAmtExpr AS paid_amount",
                "$statusExpr AS item_status",
                "t.created_at",
                "ar.request_amount AS ar_req_amount",
                "ar.approved_amount AS ar_approved_amount",
                "ar.status AS ar_status",
                "ar.approved_by AS ar_approved_by",
                "ar.approved_at AS ar_approved_at",
                "ar.accounts_head_by AS ar_accounts_head_by",
                "ar.accounts_head_at AS ar_accounts_head_at"
            ];
            
            foreach ($roles as $roleName => $cols) {
                $selectCols[] = "{$cols['by']} AS `{$roleName}_by`";
                $selectCols[] = "{$cols['at']} AS `{$roleName}_at`";
            }
            $selectColsStr = implode(", ", $selectCols);

            // Latest approval_requests subquery to avoid duplicate cartesian product rows
            $queryStr = "SELECT $selectColsStr, $casesMyAt AS my_action_time, $casesMyRole AS my_role 
                         FROM $table t 
                         LEFT JOIN (
                             SELECT source_table, source_id, MAX(id) AS max_id 
                             FROM approval_requests 
                             WHERE source_table = '$table' 
                             GROUP BY source_table, source_id
                         ) latest_ar ON latest_ar.source_id = t.$idCol 
                         LEFT JOIN approval_requests ar ON ar.id = latest_ar.max_id 
                         WHERE ($whereClause)";
            
            if ($searchReqNo) {
                $numericReq = preg_replace('/[^0-9]/', '', $searchReqNo);
                if ($numericReq) {
                    $queryStr .= " AND t.$idCol = $numericReq";
                } else {
                    $queryStr .= " AND t.$idCol LIKE '%$searchReqNo%'";
                }
            }
            if ($searchDept > 0) {
                $queryStr .= " AND $reqBy IN (SELECT user_id FROM employees WHERE department_id = $searchDept)";
            }
            
            $queries[] = $queryStr;
        };

        // 1. Fund Requisition
        $addQuery(
            'Fund Requisition', 'fund_requisitions', 'id', 't.requested_by', 't.purpose', 
            't.amount', 't.qc_approved_amount', 't.approved_amount', 't.paid_amount', 't.status',
            [
                'Accounts' => ['by' => 't.paid_by', 'at' => 't.paid_at'],
                'Accounts Head' => ['by' => 'ar.accounts_head_by', 'at' => 'ar.accounts_head_at'],
                'Management' => ['by' => 't.approved_by', 'at' => 't.approved_at'],
                'QC' => ['by' => 't.qc_approved_by', 'at' => 't.qc_approved_at'],
                'Dept Head' => ['by' => 't.dept_head_approved_by', 'at' => 't.dept_head_approved_at']
            ]
        );

        // 2. Labor Requisition
        $addQuery(
            'Labor Requisition', 'con_labor_requisitions', 'id', 't.created_by', "CONCAT('Labor Requisition #', t.requisition_no)", 
            't.total_amount', 't.qc_approved_amount', 't.approved_amount', 't.paid_amount', 't.status',
            [
                'Accounts' => ['by' => 't.paid_by', 'at' => 't.updated_at'],
                'Accounts Head' => ['by' => 'ar.accounts_head_by', 'at' => 'ar.accounts_head_at'],
                'Management' => ['by' => 't.management_approved_by', 'at' => 't.management_approved_at'],
                'QC' => ['by' => 't.qc_approved_by', 'at' => 't.qc_approved_at'],
                'PM' => ['by' => 't.pm_approved_by', 'at' => 't.pm_approved_at']
            ]
        );
        
        // 3. Vendor Payment
        $addQuery(
            'Vendor Payment', 'vendor_payment_requests', 'id', 't.created_by', "CONCAT('Vendor Payment #', t.id)", 
            'COALESCE(t.net_payable_amount, t.invoice_amount)', 't.qc_approved_amount', 't.approved_amount', 't.net_payable', 't.status',
            [
                'Accounts Head' => ['by' => 'ar.accounts_head_by', 'at' => 'ar.accounts_head_at'],
                'Management' => ['by' => 't.approved_by', 'at' => 't.updated_at'],
                'QC' => ['by' => 't.qc_approved_by_id', 'at' => 't.qc_approved_at']
            ]
        );
        
        // 4. Project Other Cost
        $addQuery(
            'Project Other Cost', 'project_other_costs', 'id', 't.created_by_id', 't.purpose', 
            't.request_amount', 'NULL', 't.approved_amount', 't.payment_amount', 't.status',
            [
                'Accounts' => ['by' => 't.payment_by_id', 'at' => 't.payment_at'],
                'Accounts Head' => ['by' => 'ar.accounts_head_by', 'at' => 'ar.accounts_head_at'],
                'Management' => ['by' => 't.management_apporved_by_id', 'at' => 't.management_apporved_at'],
                'QC' => ['by' => 't.qc_audit_approved_id', 'at' => 't.qc_audit_approved_at'],
                'Dept Head' => ['by' => 't.dept_head_approved_by', 'at' => 't.dept_head_approved_at']
            ]
        );
        
        // 5. Contractor Bill
        $addQuery(
            'Contractor Bill', 'contractor_bill_requests', 'id', 't.created_by', "CONCAT('Contractor Bill #', t.id)", 
            't.bill_amount', 'NULL', 't.approved_amount', '(t.approved_amount - IFNULL(t.due_amount, 0))', 't.status',
            [
                'Accounts Head' => ['by' => 'ar.accounts_head_by', 'at' => 'ar.accounts_head_at'],
                'Management' => ['by' => 't.approved_by', 'at' => 't.updated_at'],
                'QC' => ['by' => 't.qc_approved_by_id', 'at' => 't.qc_approved_at'],
                'PM' => ['by' => 't.verified_by', 'at' => 't.verified_at']
            ]
        );
        
        // 6. Project Extra Asset
        $addQuery(
            'Project Extra Asset', 'project_extra_assets', 'id', 't.requested_by', "CONCAT('Extra Asset #', t.id)", 
            't.total_price', 'NULL', 't.total_price', 't.paid_amount', 'COALESCE(ar.status, "approved")',
            [
                'Accounts' => ['by' => 't.paid_by', 'at' => 't.paid_at'],
                'Accounts Head' => ['by' => 'ar.accounts_head_by', 'at' => 'ar.accounts_head_at'],
                'Management' => ['by' => 't.mgmt_by', 'at' => 't.mgmt_at'],
                'QC' => ['by' => 't.qc_audit_by', 'at' => 't.qc_audit_at'],
                'Operations' => ['by' => 't.operations_by', 'at' => 't.operations_at'],
                'Purchase Head' => ['by' => 't.purchase_head_by', 'at' => 't.purchase_head_at'],
                'Dept Head' => ['by' => 't.dept_head_by', 'at' => 't.dept_head_at']
            ]
        );
        
        // 7. Cashout Request
        $addQuery(
            'Cashout Request', 'cashout_requests', 'id', 't.requested_by', 't.purpose_paid_to', 
            't.amount', 'NULL', 't.approved_amount', '(t.amount - IFNULL(t.due_amount, 0))', 't.status',
            [
                'Accounts Head' => ['by' => 'ar.accounts_head_by', 'at' => 'ar.accounts_head_at'],
                'Management' => ['by' => 't.approved_by', 'at' => 't.approved_at'],
                'QC' => ['by' => 't.qc_approved_by_id', 'at' => 't.qc_approved_at']
            ]
        );
        
        // 8. Expense Adjustment
        $addQuery(
            'Expense Adjustment', 'expense_adjustments', 'id', 't.user_id', 't.title', 
            't.amount', 't.qc_approved_by', 't.approved_amount', '(t.amount - IFNULL(t.due_amount, 0))', 't.status',
            [
                'Accounts Head' => ['by' => 'ar.accounts_head_by', 'at' => 'ar.accounts_head_at'],
                'Management' => ['by' => 't.approved_by', 'at' => 't.approved_at'],
                'QC' => ['by' => 't.qc_approved_by', 'at' => 't.qc_approved_at']
            ]
        );

        // 9. Land Fund Requisition
        $addQuery(
            'Land Fund Requisition', 'land_fund_requisitions', 'id', 't.requested_by', 't.purpose', 
            't.requested_amount', 'NULL', 'ar.approved_amount', 't.requested_amount', 'COALESCE(t.accounts_status, t.mgmt_status, ar.status, "approved")',
            [
                'Accounts' => ['by' => 't.accounts_by', 'at' => 't.accounts_paid_at'],
                'Accounts Head' => ['by' => 'ar.accounts_head_by', 'at' => 'ar.accounts_head_at'],
                'Management' => ['by' => 't.mgmt_by', 'at' => 't.mgmt_approved_at']
            ]
        );
        
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

        // Collect all related user IDs across results
        $userIds = [];
        foreach ($fullResults as $r) {
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

        $roleOrder = ['Accounts', 'Accounts Head', 'Management', 'QC', 'Operations', 'Purchase Head', 'PM', 'Dept Head'];

        $totalMyAmount = 0;
        $totalLastAmount = 0;

        $enhancedResults = [];
        foreach ($fullResults as $r) {
            $r['requested_by_name'] = $users[$r['requested_by']] ?? 'Unknown';
            
            // 1. My Action / Approved Amount ("ami koto taka korechi")
            $myRole = $r['my_role'];
            if ($myRole === 'QC') {
                $myAmt = (!empty($r['qc_amount']) && (float)$r['qc_amount'] > 0) ? (float)$r['qc_amount'] : (float)$r['req_amount'];
            } elseif ($myRole === 'Management') {
                $myAmt = (!empty($r['mgmt_amount']) && (float)$r['mgmt_amount'] > 0) 
                    ? (float)$r['mgmt_amount'] 
                    : ((!empty($r['ar_approved_amount']) && (float)$r['ar_approved_amount'] > 0) 
                        ? (float)$r['ar_approved_amount'] 
                        : ((!empty($r['qc_amount']) && (float)$r['qc_amount'] > 0) ? (float)$r['qc_amount'] : (float)$r['req_amount']));
            } elseif ($myRole === 'Accounts') {
                $myAmt = (!empty($r['paid_amount']) && (float)$r['paid_amount'] > 0)
                    ? (float)$r['paid_amount']
                    : ((!empty($r['mgmt_amount']) && (float)$r['mgmt_amount'] > 0)
                        ? (float)$r['mgmt_amount']
                        : ((!empty($r['ar_approved_amount']) && (float)$r['ar_approved_amount'] > 0) ? (float)$r['ar_approved_amount'] : (float)$r['req_amount']));
            } elseif ($myRole === 'Accounts Head') {
                $myAmt = (!empty($r['ar_approved_amount']) && (float)$r['ar_approved_amount'] > 0)
                    ? (float)$r['ar_approved_amount']
                    : ((!empty($r['mgmt_amount']) && (float)$r['mgmt_amount'] > 0) ? (float)$r['mgmt_amount'] : (float)$r['req_amount']);
            } else {
                $myAmt = (float)$r['req_amount'];
            }
            $r['my_approved_amount'] = $myAmt;

            // 2. Previous Approver (Before Me)
            $foundMe = false;
            $prevApproverId = null;
            $prevApprovedAt = null;
            $prevRole = null;
            foreach ($r as $key => $val) {
                if (strpos($key, '_by') !== false && strpos($key, 'requested_by') === false && strpos($key, 'ar_') === false) {
                    $roleKey = str_replace('_by', '', $key);
                    if ($roleKey == $myRole) {
                        $foundMe = true;
                        continue;
                    }
                    if ($foundMe && !empty($val)) {
                        $prevApproverId = (int)$val;
                        $prevApprovedAt = $r[$roleKey . '_at'] ?? null;
                        $prevRole = $roleKey;
                        break;
                    }
                }
            }
            $r['prev_approver_name'] = $prevApproverId ? ($users[$prevApproverId] ?? 'Unknown') : 'None';
            $r['prev_approved_at'] = $prevApprovedAt;
            $r['prev_role'] = $prevRole;

            // 3. Last Approver ("tini") and Last Approved Amount ("last approved er time e tini last approved amount")
            $lastApproverId = null;
            $lastApprovedAt = null;
            $lastRole = null;
            foreach ($roleOrder as $ro) {
                if (!empty($r[$ro . '_by'])) {
                    $lastApproverId = (int)$r[$ro . '_by'];
                    $lastApprovedAt = $r[$ro . '_at'] ?? null;
                    $lastRole = $ro;
                    break;
                }
            }
            if (!$lastApproverId && !empty($r['ar_approved_by'])) {
                $lastApproverId = (int)$r['ar_approved_by'];
                $lastApprovedAt = $r['ar_approved_at'] ?? null;
                $lastRole = 'Management';
            }

            $r['last_approver_id'] = $lastApproverId;
            $r['last_approver_name'] = $lastApproverId ? ($users[$lastApproverId] ?? 'Unknown') : 'None';
            $r['last_role'] = $lastRole;
            $r['last_approved_at'] = $lastApprovedAt;

            if ($lastRole === 'Accounts') {
                $lastAmt = (!empty($r['paid_amount']) && (float)$r['paid_amount'] > 0)
                    ? (float)$r['paid_amount']
                    : ((!empty($r['mgmt_amount']) && (float)$r['mgmt_amount'] > 0)
                        ? (float)$r['mgmt_amount']
                        : ((!empty($r['ar_approved_amount']) && (float)$r['ar_approved_amount'] > 0) ? (float)$r['ar_approved_amount'] : (float)$r['req_amount']));
            } elseif (in_array($lastRole, ['Accounts Head', 'Management'])) {
                $lastAmt = (!empty($r['mgmt_amount']) && (float)$r['mgmt_amount'] > 0)
                    ? (float)$r['mgmt_amount']
                    : ((!empty($r['ar_approved_amount']) && (float)$r['ar_approved_amount'] > 0)
                        ? (float)$r['ar_approved_amount']
                        : ((!empty($r['qc_amount']) && (float)$r['qc_amount'] > 0) ? (float)$r['qc_amount'] : (float)$r['req_amount']));
            } elseif ($lastRole === 'QC') {
                $lastAmt = (!empty($r['qc_amount']) && (float)$r['qc_amount'] > 0) ? (float)$r['qc_amount'] : (float)$r['req_amount'];
            } else {
                $lastAmt = (float)$r['req_amount'];
            }
            $r['last_approved_amount'] = $lastAmt;

            $totalMyAmount += $r['my_approved_amount'];
            $totalLastAmount += $r['last_approved_amount'];
            
            $enhancedResults[] = $r;
        }

        // Pagination / View All handling
        $totalItems = count($enhancedResults);
        if ($isViewAll) {
            $limit = $totalItems > 0 ? $totalItems : 1;
            $totalPages = 1;
            $paginatedResults = $enhancedResults;
        } else {
            $limit = max(1, (int)$perPageParam);
            if ($limit < 5) $limit = 20;
            $totalPages = (int)ceil($totalItems / $limit);
            $offset = ($page - 1) * $limit;
            $paginatedResults = array_slice($enhancedResults, $offset, $limit);
        }

        $deptRes = $this->db->query("SELECT id, name FROM departments ORDER BY name ASC");
        $departments = [];
        if ($deptRes) {
            while($d = $deptRes->fetch_assoc()) {
                $departments[] = $d;
            }
        }

        $data = [
            'history' => $paginatedResults,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'limit' => $limit,
            'perPage' => $isViewAll ? 'all' : (string)$limit,
            'isViewAll' => $isViewAll,
            'totalMyAmount' => $totalMyAmount,
            'totalLastAmount' => $totalLastAmount,
            'searchModule' => $searchModule,
            'searchReqNo' => $searchReqNo,
            'searchDept' => $searchDept,
            'departments' => $departments,
            'isAccounts' => $this->isAccounts
        ];

        $this->view('approvals/my-approval-history', $data);
    }
}
