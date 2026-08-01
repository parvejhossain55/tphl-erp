<?php
require_once __DIR__ . '/../core/Model.php';

class FinancialAccount extends Model
{
    public function __construct($db = null)
    {
        parent::__construct($db);
    }

    public function getProjects()
    {
        $query = "SELECT id, name as project_name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC";
        $res = $this->db->query($query);
        $data = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    public function getExpenseVouchers($type, $projectId = null)
    {
        // Type: 'fund', 'cashout', 'vendor', 'contractor'
        $data = [];
        
        if ($type === 'fund') {
            $where = $projectId ? "WHERE project_id = " . (int)$projectId : "";
            $query = "SELECT id as voucher_id, CONCAT('FR-', id) as voucher_no, created_at as entry_date, 
                             requested_by as added_by, amount, purpose as narration, status 
                      FROM fund_requisitions $where ORDER BY created_at DESC";
            $res = $this->db->query($query);
            if ($res) while ($row = $res->fetch_assoc()) $data[] = $row;
        } elseif ($type === 'cashout') {
            $where = $projectId ? "WHERE project_id = " . (int)$projectId : "";
            $query = "SELECT id as voucher_id, CONCAT('CO-', id) as voucher_no, created_at as entry_date, 
                             requested_by as added_by, amount, description as narration, status 
                      FROM cashout_requests $where ORDER BY created_at DESC";
            $res = $this->db->query($query);
            if ($res) while ($row = $res->fetch_assoc()) $data[] = $row;
        } elseif ($type === 'vendor') {
            $where = $projectId ? "WHERE project_id = " . (int)$projectId : "";
            $query = "SELECT id as voucher_id, CONCAT('VP-', id) as voucher_no, created_at as entry_date, 
                             created_by as added_by, invoice_amount as amount, payment_type as narration, status 
                      FROM vendor_payment_requests $where ORDER BY created_at DESC";
            $res = $this->db->query($query);
            if ($res) while ($row = $res->fetch_assoc()) $data[] = $row;
        } elseif ($type === 'procurement') {
            $where = $projectId ? "WHERE pea.project_id = " . (int)$projectId : "";
            $query = "SELECT pea.id as voucher_id, CONCAT('PR-', pea.id) as voucher_no, pea.created_at as entry_date, 
                             pea.requested_by as added_by, pea.total_price as amount, pea.item_name as narration, 
                             pea.overall_status as status, pea.payment_status, pea.mgmt_at as mgmt_approved_at,
                             COALESCE(u_m.full_name, 'Management') as mgmt_approved_by_name
                      FROM project_extra_assets pea 
                      LEFT JOIN users u_m ON u_m.id = pea.mgmt_by
                      $where ORDER BY pea.created_at DESC";
            $res = $this->db->query($query);
            if ($res) while ($row = $res->fetch_assoc()) $data[] = $row;
        } elseif ($type === 'contractor') {
            $where = $projectId ? "WHERE project_id = " . (int)$projectId : "";
            $query = "SELECT id as voucher_id, CONCAT('CB-', id) as voucher_no, created_at as entry_date, 
                             created_by as added_by, bill_amount as amount, work_description as narration, status 
                      FROM contractor_bill_requests $where ORDER BY created_at DESC";
            $res = $this->db->query($query);
            if ($res) while ($row = $res->fetch_assoc()) $data[] = $row;
        }
        
        return $this->attachUserNames($data);
    }

    public function getPendingPayables($projectId = null)
    {
        $pWhere = $projectId ? " AND project_id = " . (int)$projectId : "";
        $data = [];

        // 1. Office Procurement Requests (approved by mgmt, unpaid)
        $q1 = "SELECT id as voucher_id, CONCAT('PR-', id) as voucher_no, 'Procurement' as module_type, 'asset_procurement' as source_module,
                      created_at as entry_date, requested_by as added_by, total_price as amount, item_name as narration, overall_status as status, project_id
               FROM project_extra_assets 
               WHERE mgmt_status = 'approved' AND (payment_status IS NULL OR payment_status != 'paid') $pWhere";
        $r1 = $this->db->query($q1);
        if ($r1) while ($row = $r1->fetch_assoc()) $data[] = $row;

        // 2. Fund Requisitions (approved, pending payment)
        $q2 = "SELECT id as voucher_id, CONCAT('FR-', id) as voucher_no, 'Fund Requisition' as module_type, 'fund_requisition' as source_module,
                      created_at as entry_date, requested_by as added_by, amount, purpose as narration, status, project_id
               FROM fund_requisitions 
               WHERE status = 'approved' $pWhere";
        $r2 = $this->db->query($q2);
        if ($r2) while ($row = $r2->fetch_assoc()) $data[] = $row;

        // 3. Cashout Requests
        $q3 = "SELECT id as voucher_id, CONCAT('CO-', id) as voucher_no, 'Cashout Request' as module_type, 'cashout_request' as source_module,
                      created_at as entry_date, 1 as added_by, amount, 'Cashout Request' as narration, status, project_id
               FROM cashout_requests 
               WHERE status = 'approved' $pWhere";
        $r3 = $this->db->query($q3);
        if ($r3) while ($row = $r3->fetch_assoc()) $data[] = $row;

        // 4. Vendor Payment Requests
        $q4 = "SELECT id as voucher_id, CONCAT('VP-', id) as voucher_no, 'Vendor Bill' as module_type, 'vendor_payment' as source_module,
                      created_at as entry_date, created_by as added_by, invoice_amount as amount, payment_type as narration, status, project_id
               FROM vendor_payment_requests 
               WHERE status = 'approved' $pWhere";
        $r4 = $this->db->query($q4);
        if ($r4) while ($row = $r4->fetch_assoc()) $data[] = $row;

        // 5. Contractor Bill Requests
        $q5 = "SELECT id as voucher_id, CONCAT('CB-', id) as voucher_no, 'Contractor Bill' as module_type, 'contractor_bill' as source_module,
                      created_at as entry_date, created_by as added_by, bill_amount as amount, work_description as narration, status, project_id
               FROM contractor_bill_requests 
               WHERE status = 'approved' $pWhere";
        $r5 = $this->db->query($q5);
        if ($r5) while ($row = $r5->fetch_assoc()) $data[] = $row;

        usort($data, function($a, $b) {
            return strtotime($b['entry_date']) - strtotime($a['entry_date']);
        });

        return $this->attachUserNames($data);
    }

    public function getAdjustments($projectId = null)
    {
        $where = $projectId ? "WHERE project_id = " . (int)$projectId : "";
        $query = "SELECT id as voucher_id, CONCAT('ADJ-', id) as voucher_no, created_at as entry_date, 
                         user_id as added_by, amount, description as narration, status, adjustment_type 
                  FROM expense_adjustments $where ORDER BY created_at DESC";
        $res = $this->db->query($query);
        $data = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) $data[] = $row;
        }
        return $this->attachUserNames($data);
    }

    public function getReceipts($projectId = null)
    {
        $where = $projectId ? "WHERE b.project_id = " . (int)$projectId : "";
        $query = "SELECT p.id as voucher_id, CONCAT('REC-', p.id) as voucher_no, p.created_at as entry_date, 
                         p.created_by as added_by, p.amount, p.payment_type as narration, p.payment_status as status,
                         l.full_name as client_name 
                  FROM lead_payments p
                  LEFT JOIN lead_bookings b ON p.booking_id = b.id
                  LEFT JOIN leads l ON b.lead_id = l.id
                  $where
                  ORDER BY p.created_at DESC";
        $res = $this->db->query($query);
        $data = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) $data[] = $row;
        }
        return $this->attachUserNames($data);
    }

    public function getClientsWithPendingPayments($projectId = null)
    {
        $where = $projectId ? "AND b.project_id = " . (int)$projectId : "";
        $query = "SELECT b.id as booking_id, l.full_name, l.phone, f.price_value as total_price,
                         p.name as project_name, f.flat_name as flat_name,
                         (SELECT SUM(amount) FROM lead_payments WHERE booking_id = b.id AND payment_status IN ('paid', 'partially_paid')) as total_received
                  FROM lead_bookings b
                  JOIN leads l ON b.lead_id = l.id
                  LEFT JOIN projects p ON b.project_id = p.id
                  LEFT JOIN flats f ON b.flat_id = f.id
                  WHERE b.booking_status IN ('booked', 'completed') $where";
        $res = $this->db->query($query);
        $data = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $row['total_pending'] = (float)$row['total_price'] - (float)$row['total_received'];
                $data[] = $row;
            }
        }
        return $data;
    }

    private function attachUserNames($data)
    {
        if (empty($data)) return $data;
        $userIds = array_filter(array_unique(array_column($data, 'added_by')));
        if (empty($userIds)) return $data;
        
        $ids = implode(',', array_map('intval', $userIds));
        $query = "SELECT id, full_name FROM users WHERE id IN ($ids)";
        $res = $this->db->query($query);
        $users = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $users[$row['id']] = $row['full_name'];
            }
        }
        
        foreach ($data as &$row) {
            $row['added_by_name'] = $users[$row['added_by']] ?? 'System';
        }
        
        return $data;
    }
    public function getRefunds($projectId = null)
    {
        $where = $projectId ? "AND r.project_id = " . (int)$projectId : "";
        $query = "SELECT r.id as refund_id, r.booking_id, l.full_name as client_name, p.name as project_name, f.flat_name as flat_no,
                         r.total_paid, r.retention_amount, r.refund_amount, r.reason, r.status, r.refunded_at
                  FROM client_refund_requests r
                  LEFT JOIN leads l ON r.lead_id = l.id
                  LEFT JOIN projects p ON r.project_id = p.id
                  LEFT JOIN flats f ON r.flat_id = f.id
                  WHERE r.status = 'refunded' $where
                  ORDER BY r.created_at DESC";
        $res = $this->db->query($query);
        $data = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) $data[] = $row;
        }
        return $data;
    }
}
