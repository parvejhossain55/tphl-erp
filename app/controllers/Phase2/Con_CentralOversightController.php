<?php
namespace Phase2;

use Controller;

class Con_CentralOversightController extends Controller
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }

        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        // Accessible by Management (1, 3, 33, 34, 35), Project Director / Dept. Head (22), Operation Head (20, 32)
        if (!in_array($roleId, [1, 3, 20, 22, 32, 33, 34, 35], true)) {
            setError('You do not have permission to access the Central Construction Oversight Dashboard.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }
    }

    public function index()
    {
        require_once __DIR__ . '/../../models/Con_ApprovalLog.php';
        $logModel = new \Con_ApprovalLog($this->db);
        $logModel->ensureSchema();

        $projectId  = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $approverId = !empty($_GET['approver_id']) ? (int)$_GET['approver_id'] : null;
        $reqType    = trim($_GET['req_type'] ?? '');
        $allowedReqTypes = ['', 'purchase_requests', 'con_labor_requisitions', 'fund_requisitions', 'contractor_bill_requests', 'initial_estimate_budgets', 'boq_master'];
        if (!in_array($reqType, $allowedReqTypes, true)) {
            $reqType = '';
        }
        $range      = trim($_GET['range'] ?? 'all');
        $startDate  = trim($_GET['start_date'] ?? '');
        $endDate    = trim($_GET['end_date'] ?? '');

        if ($range === 'today') {
            $startDate = date('Y-m-d');
            $endDate   = date('Y-m-d');
        } elseif ($range === 'month') {
            $startDate = date('Y-m-01');
            $endDate   = date('Y-m-t');
        } elseif ($range === '30d') {
            $startDate = date('Y-m-d', strtotime('-30 days'));
            $endDate   = date('Y-m-d');
        } elseif ($range === '7d') {
            $startDate = date('Y-m-d', strtotime('-7 days'));
            $endDate   = date('Y-m-d');
        } elseif ($range !== 'custom') {
            $startDate = '';
            $endDate   = '';
        }

        // Projects list for filter
        $projectsRes = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND under_construction = 1 ORDER BY name ASC");
        $projects = $projectsRes ? $projectsRes->fetch_all(MYSQLI_ASSOC) : [];

        // Approvers list for filter (PD role 22, PC role 15, Management 1,3,33,34,35)
        $approversRes = $this->db->query("SELECT u.id, u.full_name, u.role_id, r.name AS role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.role_id IN (1, 3, 15, 22, 33, 34, 35) ORDER BY FIELD(u.role_id, 22, 15, 1, 3, 33, 34, 35), u.full_name ASC");
        $approversList = $approversRes ? $approversRes->fetch_all(MYSQLI_ASSOC) : [];

        // 1. Unified Approval Accountability Data
        $approvalData = $this->getUnifiedApprovals($projectId, $approverId, $startDate, $endDate, $reqType);

        // 2. Pending count for KPI strip
        $pendingQueue = $this->getPendingQueue($projectId);

        // 3. Project-wise Materials & Delivery Flow
        $materialPipeline = $this->getProjectMaterialPipeline($projectId);

        // 4. Financial Snapshot (Budget vs PO Committed vs Paid)
        $financialSnapshot = $this->getFinancialSnapshot($projectId);

        // 5. KPI Summary
        $kpis = $this->computeKpis($projects, $materialPipeline, $pendingQueue, $approvalData, $projectId);

        $this->view('phase2/dashboard/central-oversight', [
            'current_route'     => 'con-central-oversight',
            'page_title'        => 'Central Construction Oversight',
            'projects'          => $projects,
            'approversList'     => $approversList,
            'filters'           => [
                'project_id'  => $projectId,
                'approver_id' => $approverId,
                'req_type'    => $reqType,
                'range'       => $range,
                'start_date'  => $startDate,
                'end_date'    => $endDate,
            ],
            'kpis'              => $kpis,
            'approverSummary'   => $approvalData['summary'],
            'materialPipeline'  => $materialPipeline,
            'financialSnapshot' => $financialSnapshot,
        ]);
    }

    /**
     * Build unified approval records across con_approval_logs and historical columns
     * so both new and past approvals show Dept. Head vs PC (On behalf of Dept. Head).
     */
    private function getUnifiedApprovals($projectId = null, $approverId = null, $startDate = '', $endDate = '', $reqType = '')
    {
        $events = [];
        $seenKeys = [];

        $addEvent = function ($row) use (&$events, &$seenKeys) {
            $uid = (int)($row['actioned_by'] ?? 0);
            if ($uid <= 0) {
                return;
            }
            $key = $row['ref_table'] . ':' . (int)$row['ref_id'] . ':' . $uid;
            if (isset($seenKeys[$key])) {
                return;
            }
            $seenKeys[$key] = true;

            $roleId = (int)($row['actioned_role_id'] ?? 0);
            $name   = $row['approver_name'] ?? 'User';
            $status = strtolower((string)($row['status'] ?? 'approved'));
            $attr   = \Con_ApprovalLog::formatApprovalAttribution($roleId, $name, $status);

            $row['role_label']     = $attr['role_label'];
            $row['on_behalf_of']   = $row['on_behalf_of'] ?? $attr['on_behalf_of'];
            $row['approval_label'] = !empty($row['approval_label']) ? $row['approval_label'] : $attr['approval_label'];
            $events[] = $row;
        };

        // A. Direct entries from con_approval_logs
        $whereLog = ["1=1"];
        if ($reqType !== '') {
            $whereLog[] = "cal.ref_table = '" . $this->db->real_escape_string($reqType) . "'";
        }
        if ($projectId) {
            $whereLog[] = "cal.project_id = " . (int)$projectId;
        }
        if ($approverId) {
            $whereLog[] = "cal.actioned_by = " . (int)$approverId;
        }
        if ($startDate !== '') {
            $whereLog[] = "DATE(cal.created_at) >= '" . $this->db->real_escape_string($startDate) . "'";
        }
        if ($endDate !== '') {
            $whereLog[] = "DATE(cal.created_at) <= '" . $this->db->real_escape_string($endDate) . "'";
        }

        $sqlLogs = "
            SELECT cal.ref_table, cal.ref_id, cal.project_id, p.name AS project_name,
                   cal.step_name, cal.amount, cal.actioned_by,
                   COALESCE(cal.actioned_role_id, u.role_id) AS actioned_role_id,
                   cal.on_behalf_of, cal.approval_label, cal.status, cal.remarks, cal.created_at,
                   u.full_name AS approver_name
            FROM con_approval_logs cal
            LEFT JOIN users u ON u.id = cal.actioned_by
            LEFT JOIN projects p ON p.id = cal.project_id
            WHERE " . implode(' AND ', $whereLog) . "
            ORDER BY cal.created_at DESC
            LIMIT 500
        ";
        if ($res = $this->db->query($sqlLogs)) {
            while ($r = $res->fetch_assoc()) {
                $r['doc_type'] = $this->mapRefTableLabel($r['ref_table']);
                $r['doc_url']  = $this->mapRefTableUrl($r['ref_table'], $r['ref_id']);
                $addEvent($r);
            }
        }

        // B. Historical backfill from Purchase Requests
        if ($reqType === '' || $reqType === 'purchase_requests') {
            $prConds = ["pr.approved_by_id IS NOT NULL", "pr.status IN ('Approved', 'PO Issued')"];
            if ($projectId)  $prConds[] = "pr.project_id = " . (int)$projectId;
            if ($approverId) $prConds[] = "pr.approved_by_id = " . (int)$approverId;
            if ($startDate !== '') $prConds[] = "DATE(COALESCE(pr.approved_at, pr.created_at)) >= '" . $this->db->real_escape_string($startDate) . "'";
            if ($endDate !== '')   $prConds[] = "DATE(COALESCE(pr.approved_at, pr.created_at)) <= '" . $this->db->real_escape_string($endDate) . "'";

            $sqlPR = "
                SELECT 'purchase_requests' AS ref_table, pr.id AS ref_id, pr.project_id, p.name AS project_name,
                       'Dept. Head Approval' AS step_name,
                       COALESCE((SELECT SUM(COALESCE(pri.total_price, pri.quantity * COALESCE(m.standard_price, 0))) FROM purchase_request_items pri LEFT JOIN con_materials m ON m.id = pri.material_id WHERE pri.request_id = pr.id), 0) AS amount,
                       pr.approved_by_id AS actioned_by, u.role_id AS actioned_role_id,
                       u.full_name AS approver_name, 'approved' AS status, pr.management_note AS remarks,
                       COALESCE(pr.approved_at, pr.created_at) AS created_at
                FROM purchase_requests pr
                LEFT JOIN users u ON u.id = pr.approved_by_id
                LEFT JOIN projects p ON p.id = pr.project_id
                WHERE " . implode(' AND ', $prConds) . "
                ORDER BY created_at DESC LIMIT 300
            ";
            if ($res = $this->db->query($sqlPR)) {
                while ($r = $res->fetch_assoc()) {
                    $r['doc_type'] = 'Purchase Request';
                    $r['doc_url']  = 'con-purchase-request/' . $r['ref_id'];
                    $addEvent($r);
                }
            }
        }

        // C. Historical backfill from Initial Estimates
        if ($reqType === '' || $reqType === 'initial_estimate_budgets') {
            $estConds = ["ieb.approved_by IS NOT NULL", "ieb.status IN ('pm_approved', 'approved')"];
            if ($projectId)  $estConds[] = "ieb.project_id = " . (int)$projectId;
            if ($approverId) $estConds[] = "ieb.approved_by = " . (int)$approverId;
            if ($startDate !== '') $estConds[] = "DATE(COALESCE(ieb.approved_at, ieb.created_at)) >= '" . $this->db->real_escape_string($startDate) . "'";
            if ($endDate !== '')   $estConds[] = "DATE(COALESCE(ieb.approved_at, ieb.created_at)) <= '" . $this->db->real_escape_string($endDate) . "'";

            $sqlEst = "
                SELECT 'initial_estimate_budgets' AS ref_table, ieb.id AS ref_id, ieb.project_id, p.name AS project_name,
                       IF(ieb.status = 'pm_approved', 'Dept. Head Approval', 'Management Final Approval') AS step_name,
                       COALESCE(ieb.total_estimated_cost, 0) AS amount,
                       ieb.approved_by AS actioned_by, u.role_id AS actioned_role_id,
                       u.full_name AS approver_name, 'approved' AS status, '' AS remarks,
                       COALESCE(ieb.approved_at, ieb.created_at) AS created_at
                FROM initial_estimate_budgets ieb
                LEFT JOIN users u ON u.id = ieb.approved_by
                LEFT JOIN projects p ON p.id = ieb.project_id
                WHERE " . implode(' AND ', $estConds) . "
                ORDER BY created_at DESC LIMIT 200
            ";
            if ($res = $this->db->query($sqlEst)) {
                while ($r = $res->fetch_assoc()) {
                    $r['doc_type'] = 'Initial Estimate';
                    $r['doc_url']  = 'con-estimate-show/' . $r['ref_id'];
                    $addEvent($r);
                }
            }
        }

        // D. Historical backfill from Contractor Bills (Dept. Head / Verified step)
        if ($reqType === '' || $reqType === 'contractor_bill_requests') {
            $cbConds = ["cbr.verified_by IS NOT NULL", "cbr.status != 'rejected'"];
            if ($projectId)  $cbConds[] = "cbr.project_id = " . (int)$projectId;
            if ($approverId) $cbConds[] = "cbr.verified_by = " . (int)$approverId;
            if ($startDate !== '') $cbConds[] = "DATE(COALESCE(cbr.verified_at, cbr.created_at)) >= '" . $this->db->real_escape_string($startDate) . "'";
            if ($endDate !== '')   $cbConds[] = "DATE(COALESCE(cbr.verified_at, cbr.created_at)) <= '" . $this->db->real_escape_string($endDate) . "'";

            $sqlCB = "
                SELECT 'contractor_bill_requests' AS ref_table, cbr.id AS ref_id, cbr.project_id, p.name AS project_name,
                       'Dept. Head Approval' AS step_name,
                       COALESCE(cbr.bill_amount, 0) AS amount,
                       cbr.verified_by AS actioned_by, u.role_id AS actioned_role_id,
                       u.full_name AS approver_name, 'approved' AS status, cbr.verification_note AS remarks,
                       COALESCE(cbr.verified_at, cbr.created_at) AS created_at
                FROM contractor_bill_requests cbr
                LEFT JOIN users u ON u.id = cbr.verified_by
                LEFT JOIN projects p ON p.id = cbr.project_id
                WHERE " . implode(' AND ', $cbConds) . "
                ORDER BY created_at DESC LIMIT 200
            ";
            if ($res = $this->db->query($sqlCB)) {
                while ($r = $res->fetch_assoc()) {
                    $r['doc_type'] = 'Contractor Bill';
                    $r['doc_url']  = 'con-contractor-bill/' . $r['ref_id'];
                    $addEvent($r);
                }
            }
        }

        // E. Historical backfill from Labor Requisitions
        if ($reqType === '' || $reqType === 'con_labor_requisitions') {
            $lrConds = ["clr.pm_approved_by IS NOT NULL", "clr.status != 'rejected'"];
            if ($projectId)  $lrConds[] = "clr.project_id = " . (int)$projectId;
            if ($approverId) $lrConds[] = "clr.pm_approved_by = " . (int)$approverId;
            if ($startDate !== '') $lrConds[] = "DATE(COALESCE(clr.pm_approved_at, clr.created_at)) >= '" . $this->db->real_escape_string($startDate) . "'";
            if ($endDate !== '')   $lrConds[] = "DATE(COALESCE(clr.pm_approved_at, clr.created_at)) <= '" . $this->db->real_escape_string($endDate) . "'";

            $sqlLR = "
                SELECT 'con_labor_requisitions' AS ref_table, clr.id AS ref_id, clr.project_id, p.name AS project_name,
                       'Dept. Head Approval' AS step_name,
                       COALESCE(clr.total_amount, 0) AS amount,
                       clr.pm_approved_by AS actioned_by, u.role_id AS actioned_role_id,
                       u.full_name AS approver_name, 'approved' AS status, clr.pm_remarks AS remarks,
                       COALESCE(clr.pm_approved_at, clr.created_at) AS created_at
                FROM con_labor_requisitions clr
                LEFT JOIN users u ON u.id = clr.pm_approved_by
                LEFT JOIN projects p ON p.id = clr.project_id
                WHERE " . implode(' AND ', $lrConds) . "
                ORDER BY created_at DESC LIMIT 200
            ";
            if ($res = $this->db->query($sqlLR)) {
                while ($r = $res->fetch_assoc()) {
                    $r['doc_type'] = 'Labor Requisition';
                    $r['doc_url']  = 'con-labor-requisition/' . $r['ref_id'];
                    $addEvent($r);
                }
            }
        }

        // F. Historical backfill from Fund Requisitions (under construction / approved by PD or PC)
        if ($reqType === '' || $reqType === 'fund_requisitions') {
            $frConds = ["fr.dept_approved_by IS NOT NULL", "fr.status != 'rejected'", "(p.under_construction = 1 OR u.role_id IN (15, 22))"];
            if ($projectId)  $frConds[] = "fr.project_id = " . (int)$projectId;
            if ($approverId) $frConds[] = "fr.dept_approved_by = " . (int)$approverId;
            if ($startDate !== '') $frConds[] = "DATE(COALESCE(fr.dept_approved_at, fr.created_at)) >= '" . $this->db->real_escape_string($startDate) . "'";
            if ($endDate !== '')   $frConds[] = "DATE(COALESCE(fr.dept_approved_at, fr.created_at)) <= '" . $this->db->real_escape_string($endDate) . "'";

            $sqlFR = "
                SELECT 'fund_requisitions' AS ref_table, fr.id AS ref_id, fr.project_id, p.name AS project_name,
                       'Dept. Head Approval' AS step_name,
                       COALESCE(fr.amount, 0) AS amount,
                       fr.dept_approved_by AS actioned_by, u.role_id AS actioned_role_id,
                       u.full_name AS approver_name, 'approved' AS status, fr.purpose AS remarks,
                       COALESCE(fr.dept_approved_at, fr.created_at) AS created_at
                FROM fund_requisitions fr
                LEFT JOIN users u ON u.id = fr.dept_approved_by
                LEFT JOIN projects p ON p.id = fr.project_id
                WHERE " . implode(' AND ', $frConds) . "
                ORDER BY created_at DESC LIMIT 200
            ";
            if ($res = $this->db->query($sqlFR)) {
                while ($r = $res->fetch_assoc()) {
                    $r['doc_type'] = 'Fund Requisition';
                    $r['doc_url']  = 'fund-requisitions/view/' . $r['ref_id'];
                    $addEvent($r);
                }
            }
        }

        // Sort newest first
        usort($events, function ($a, $b) {
            return strtotime($b['created_at'] ?? '1970-01-01') <=> strtotime($a['created_at'] ?? '1970-01-01');
        });

        // Aggregate per approver
        $summaryMap = [];
        foreach ($events as $ev) {
            if (($ev['status'] ?? 'approved') !== 'approved') {
                continue;
            }
            $uid = (int)$ev['actioned_by'];
            if (!isset($summaryMap[$uid])) {
                $summaryMap[$uid] = [
                    'approver_id'   => $uid,
                    'approver_name' => $ev['approver_name'] ?? 'User',
                    'role_id'       => (int)($ev['actioned_role_id'] ?? 0),
                    'role_label'    => $ev['role_label'],
                    'on_behalf_of'  => $ev['on_behalf_of'],
                    'pr_count'      => 0,
                    'labor_count'   => 0,
                    'fund_count'    => 0,
                    'est_boq_count' => 0,
                    'bill_count'    => 0,
                    'total_count'   => 0,
                    'total_amount'  => 0.0,
                ];
            }
            $summaryMap[$uid]['total_count']++;
            $summaryMap[$uid]['total_amount'] += (float)($ev['amount'] ?? 0);

            $rt = $ev['ref_table'];
            if ($rt === 'purchase_requests') {
                $summaryMap[$uid]['pr_count']++;
            } elseif ($rt === 'con_labor_requisitions') {
                $summaryMap[$uid]['labor_count']++;
            } elseif (in_array($rt, ['fund_requisitions', 'operational_fund_requests'], true)) {
                $summaryMap[$uid]['fund_count']++;
            } elseif (in_array($rt, ['initial_estimate_budgets', 'boq_master'], true)) {
                $summaryMap[$uid]['est_boq_count']++;
            } else {
                $summaryMap[$uid]['bill_count']++;
            }
        }

        $summary = array_values($summaryMap);
        usort($summary, function ($a, $b) {
            return $b['total_count'] <=> $a['total_count'];
        });

        return [
            'summary' => $summary,
            'logs'    => array_slice($events, 0, 200),
        ];
    }

    private function getPendingQueue($projectId = null)
    {
        $queue = [];
        $pFilter = $projectId ? " AND pr.project_id = " . (int)$projectId : "";

        // 1. Purchase Requests awaiting Dept. Head / Management
        $sqlPR = "
            SELECT pr.id, pr.project_id, p.name AS project_name, pr.created_at,
                   u.full_name AS requester_name,
                   DATEDIFF(NOW(), pr.created_at) AS aging_days,
                   COALESCE((SELECT SUM(COALESCE(pri.total_price, pri.quantity * COALESCE(m.standard_price, 0))) FROM purchase_request_items pri LEFT JOIN con_materials m ON m.id = pri.material_id WHERE pri.request_id = pr.id), 0) AS amount,
                   IF(pr.approved_by_id IS NULL, 'Awaiting Dept. Head Approval', 'Awaiting Op/Mgmt Approval') AS stage
            FROM purchase_requests pr
            LEFT JOIN projects p ON p.id = pr.project_id
            LEFT JOIN users u ON u.id = pr.created_by
            WHERE pr.status = 'Submitted' {$pFilter}
            ORDER BY pr.created_at ASC LIMIT 30
        ";
        if ($res = $this->db->query($sqlPR)) {
            while ($r = $res->fetch_assoc()) {
                $queue[] = [
                    'type'         => 'Purchase Request',
                    'ref_no'       => 'PR-' . $r['id'],
                    'project_name' => $r['project_name'] ?? '—',
                    'requester'    => $r['requester_name'] ?? '—',
                    'stage'        => $r['stage'],
                    'amount'       => (float)$r['amount'],
                    'aging_days'   => (int)$r['aging_days'],
                    'url'          => 'con-purchase-request/' . $r['id'],
                ];
            }
        }

        // 2. Initial Estimates awaiting Dept. Head or Management
        $pFilterEst = $projectId ? " AND ieb.project_id = " . (int)$projectId : "";
        $sqlEst = "
            SELECT ieb.id, p.name AS project_name, ieb.total_estimated_cost AS amount, ieb.status, ieb.created_at,
                   u.full_name AS requester_name, DATEDIFF(NOW(), ieb.created_at) AS aging_days
            FROM initial_estimate_budgets ieb
            LEFT JOIN projects p ON p.id = ieb.project_id
            LEFT JOIN users u ON u.id = ieb.created_by
            WHERE ieb.status IN ('pending', 'pm_approved') {$pFilterEst}
            ORDER BY ieb.created_at ASC LIMIT 30
        ";
        if ($res = $this->db->query($sqlEst)) {
            while ($r = $res->fetch_assoc()) {
                $queue[] = [
                    'type'         => 'Initial Estimate',
                    'ref_no'       => 'EST-' . $r['id'],
                    'project_name' => $r['project_name'] ?? '—',
                    'requester'    => $r['requester_name'] ?? '—',
                    'stage'        => $r['status'] === 'pending' ? 'Awaiting Dept. Head Approval' : 'Awaiting Management Final',
                    'amount'       => (float)$r['amount'],
                    'aging_days'   => (int)$r['aging_days'],
                    'url'          => 'con-estimate-show/' . $r['id'],
                ];
            }
        }

        // 3. Contractor Bills awaiting Dept. Head / Management
        $pFilterCB = $projectId ? " AND cbr.project_id = " . (int)$projectId : "";
        $sqlCB = "
            SELECT cbr.id, p.name AS project_name, cbr.bill_amount AS amount, cbr.status, cbr.created_at,
                   c.name AS contractor_name, DATEDIFF(NOW(), cbr.created_at) AS aging_days
            FROM contractor_bill_requests cbr
            LEFT JOIN projects p ON p.id = cbr.project_id
            LEFT JOIN contractors c ON c.id = cbr.operator_id
            WHERE cbr.status IN ('submitted', 'verified') {$pFilterCB}
            ORDER BY cbr.created_at ASC LIMIT 30
        ";
        if ($res = $this->db->query($sqlCB)) {
            while ($r = $res->fetch_assoc()) {
                $queue[] = [
                    'type'         => 'Contractor Bill',
                    'ref_no'       => 'BILL-' . $r['id'],
                    'project_name' => $r['project_name'] ?? '—',
                    'requester'    => $r['contractor_name'] ?? 'Contractor',
                    'stage'        => $r['status'] === 'submitted' ? 'Awaiting Dept. Head Approval' : 'Awaiting Purchase/Mgmt Approval',
                    'amount'       => (float)$r['amount'],
                    'aging_days'   => (int)$r['aging_days'],
                    'url'          => 'con-contractor-bill/' . $r['id'],
                ];
            }
        }

        usort($queue, function ($a, $b) {
            return $b['aging_days'] <=> $a['aging_days'];
        });

        return $queue;
    }

    private function getProjectMaterialPipeline($projectId = null)
    {
        $whereProj = "p.deleted_at IS NULL AND p.under_construction = 1";
        if ($projectId) {
            $whereProj .= " AND p.id = " . (int)$projectId;
        }

        $sql = "
            SELECT p.id AS project_id, p.name AS project_name,
                   COALESCE(pr_stats.pr_count, 0) AS pr_count,
                   COALESCE(pr_stats.delayed_prs, 0) AS delayed_prs,
                   COALESCE(po_stats.po_count, 0) AS po_count,
                   COALESCE(po_stats.po_ordered_value, 0) AS po_ordered_value,
                   COALESCE(po_stats.po_ordered_qty, 0) AS po_ordered_qty,
                   COALESCE(rec_stats.delivered_qty, 0) AS delivered_qty,
                   COALESCE(rec_stats.delivered_value, 0) AS delivered_value
            FROM projects p
            LEFT JOIN (
                SELECT pr.project_id,
                       COUNT(DISTINCT pr.id) AS pr_count,
                       SUM(CASE WHEN pr.status IN ('Submitted', 'Approved', 'PO Issued') AND DATEDIFF(NOW(), pr.created_at) > 7 THEN 1 ELSE 0 END) AS delayed_prs
                FROM purchase_requests pr
                WHERE pr.status != 'Rejected'
                GROUP BY pr.project_id
            ) pr_stats ON pr_stats.project_id = p.id
            LEFT JOIN (
                SELECT pr.project_id,
                       COUNT(DISTINCT po.id) AS po_count,
                       COALESCE(SUM(poi.quantity * poi.unit_price), 0) AS po_ordered_value,
                       COALESCE(SUM(poi.quantity), 0) AS po_ordered_qty
                FROM purchase_orders po
                JOIN purchase_requests pr ON pr.id = po.request_id
                JOIN purchase_order_items poi ON poi.purchase_order_id = po.id
                WHERE po.status != 'Cancelled'
                GROUP BY pr.project_id
            ) po_stats ON po_stats.project_id = p.id
            LEFT JOIN (
                SELECT pr.project_id,
                       COALESCE(SUM(mri.received_quantity), 0) AS delivered_qty,
                       COALESCE(SUM(mri.received_quantity * poi.unit_price), 0) AS delivered_value
                FROM material_receiving_items mri
                JOIN material_receiving mr ON mr.id = mri.receiving_id
                JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
                JOIN purchase_orders po ON po.id = poi.purchase_order_id
                JOIN purchase_requests pr ON pr.id = po.request_id
                WHERE mri.status = 'Accepted'
                GROUP BY pr.project_id
            ) rec_stats ON rec_stats.project_id = p.id
            WHERE {$whereProj}
            ORDER BY po_ordered_value DESC, p.name ASC
        ";

        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    private function getFinancialSnapshot($projectId = null)
    {
        $whereProj = "p.deleted_at IS NULL AND p.under_construction = 1";
        if ($projectId) {
            $whereProj .= " AND p.id = " . (int)$projectId;
        }

        $sql = "
            SELECT p.id AS project_id, p.name AS project_name,
                   COALESCE(bud.approved_budget, 0) AS approved_budget,
                   COALESCE(po_val.po_committed, 0) AS po_committed,
                   COALESCE(paid.total_paid, 0) AS total_paid
            FROM projects p
            LEFT JOIN (
                SELECT project_id, SUM(total_estimated_cost) AS approved_budget
                FROM initial_estimate_budgets
                WHERE status IN ('approved', 'pm_approved')
                GROUP BY project_id
            ) bud ON bud.project_id = p.id
            LEFT JOIN (
                SELECT pr.project_id, SUM(poi.quantity * poi.unit_price) AS po_committed
                FROM purchase_orders po
                JOIN purchase_requests pr ON pr.id = po.request_id
                JOIN purchase_order_items poi ON poi.purchase_order_id = po.id
                WHERE po.status != 'Cancelled'
                GROUP BY pr.project_id
            ) po_val ON po_val.project_id = p.id
            LEFT JOIN (
                SELECT project_id, SUM(amount_paid) AS total_paid
                FROM con_financial_transactions
                GROUP BY project_id
            ) paid ON paid.project_id = p.id
            WHERE {$whereProj}
            ORDER BY approved_budget DESC, p.name ASC
        ";

        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    private function computeKpis($projects, $materialPipeline, $pendingQueue, $approvalData, $projectId = null)
    {
        $totalOrderedValue   = array_sum(array_column($materialPipeline, 'po_ordered_value'));
        $totalDeliveredValue = array_sum(array_column($materialPipeline, 'delivered_value'));
        $totalDelayedPrs     = array_sum(array_column($materialPipeline, 'delayed_prs'));

        $totalApprovedCount  = array_sum(array_column($approvalData['summary'], 'total_count'));
        $totalApprovedValue  = array_sum(array_column($approvalData['summary'], 'total_amount'));

        $stockRes = $this->db->query("SELECT COALESCE(SUM(cs.current_quantity * COALESCE(m.standard_price, 0)), 0) AS stock_val FROM con_stocks cs LEFT JOIN con_materials m ON m.id = cs.material_id");
        $stockValue = $stockRes ? (float)($stockRes->fetch_assoc()['stock_val'] ?? 0) : 0.0;

        return [
            'active_projects'       => $projectId ? 1 : count($projects),
            'po_ordered_value'      => $totalOrderedValue,
            'delivered_value'       => $totalDeliveredValue,
            'stock_value'           => $stockValue,
            'pending_approvals'     => count($pendingQueue),
            'delayed_items'         => (int)$totalDelayedPrs,
            'total_approved_count'  => (int)$totalApprovedCount,
            'total_approved_value'  => (float)$totalApprovedValue,
        ];
    }

    public function ajaxProjectMaterials()
    {
        header('Content-Type: application/json');
        $pid = (int)($_GET['project_id'] ?? 0);
        if ($pid <= 0) {
            echo json_encode(['items' => []]);
            exit;
        }

        $sql = "
            SELECT m.name AS material_name, m.unit,
                   SUM(poi.quantity) AS ordered_qty,
                   SUM(poi.quantity * poi.unit_price) AS ordered_val,
                   COALESCE((
                       SELECT SUM(mri.received_quantity)
                       FROM material_receiving_items mri
                       JOIN purchase_order_items poi2 ON poi2.id = mri.purchase_order_item_id
                       JOIN purchase_orders po2 ON po2.id = poi2.purchase_order_id
                       JOIN purchase_requests pr2 ON pr2.id = po2.request_id
                       WHERE pr2.project_id = pr.project_id AND poi2.material_id = poi.material_id AND mri.status = 'Accepted'
                   ), 0) AS delivered_qty
            FROM purchase_order_items poi
            JOIN purchase_orders po ON po.id = poi.purchase_order_id
            JOIN purchase_requests pr ON pr.id = po.request_id
            JOIN con_materials m ON m.id = poi.material_id
            WHERE pr.project_id = ? AND po.status != 'Cancelled'
            GROUP BY poi.material_id, m.name, m.unit, pr.project_id
            ORDER BY ordered_val DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $pid);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['items' => $items]);
        exit;
    }

    public function ajaxApproverDetails()
    {
        header('Content-Type: application/json');
        $approverId = (int)($_GET['approver_id'] ?? 0);
        $projectId  = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $reqType    = trim($_GET['req_type'] ?? '');
        $allowedReqTypes = ['', 'purchase_requests', 'con_labor_requisitions', 'fund_requisitions', 'contractor_bill_requests', 'initial_estimate_budgets', 'boq_master'];
        if (!in_array($reqType, $allowedReqTypes, true)) {
            $reqType = '';
        }
        $startDate  = trim($_GET['start_date'] ?? '');
        $endDate    = trim($_GET['end_date'] ?? '');

        if ($approverId <= 0) {
            echo json_encode(['logs' => []]);
            exit;
        }

        require_once __DIR__ . '/../../models/Con_ApprovalLog.php';
        $data = $this->getUnifiedApprovals($projectId, $approverId, $startDate, $endDate, $reqType);
        echo json_encode(['logs' => $data['logs']]);
        exit;
    }

    private function mapRefTableLabel($refTable)
    {
        $map = [
            'purchase_requests'        => 'Purchase Request',
            'initial_estimate_budgets' => 'Initial Estimate',
            'boq_master'               => 'BOQ',
            'contractor_bill_requests' => 'Contractor Bill',
            'con_labor_requisitions'   => 'Labor Requisition',
            'purchase_orders'          => 'Purchase Order',
            'operational_fund_requests'=> 'Fund Request',
            'fund_requisitions'        => 'Fund Requisition',
        ];
        return $map[$refTable] ?? ucwords(str_replace('_', ' ', $refTable));
    }

    private function mapRefTableUrl($refTable, $refId)
    {
        $map = [
            'purchase_requests'        => 'con-purchase-request/' . $refId,
            'initial_estimate_budgets' => 'con-estimate-show/' . $refId,
            'boq_master'               => 'con-boq-show/' . $refId,
            'contractor_bill_requests' => 'con-contractor-bill/' . $refId,
            'con_labor_requisitions'   => 'con-labor-requisition/' . $refId,
            'purchase_orders'          => 'con-purchase-order/' . $refId,
            'operational_fund_requests'=> 'con-fund-request/' . $refId,
            'fund_requisitions'        => 'fund-requisitions/view/' . $refId,
        ];
        return $map[$refTable] ?? '#';
    }
}
