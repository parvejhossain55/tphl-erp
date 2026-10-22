<?php
/**
 * FundRequisitionApprovalLog
 *
 * Immutable per-action history of the final (Management / Operation Head) approvals
 * on fund_requisitions. The main table only keeps the LAST approver and the CUMULATIVE
 * approved amount, so partial approvals ("who approved how much, when") were lost.
 *
 * Used by:
 *  - Fund requisition show page (timeline)
 *  - Central "All Approval Requests" list
 *  - Approval / Debit voucher audit trail
 */
class FundRequisitionApprovalLog
{
    private static $tableChecked = false;

    public static function ensureTable($db): void
    {
        if (self::$tableChecked || !$db) {
            return;
        }
        self::$tableChecked = true;
        try {
            $db->query("
                CREATE TABLE IF NOT EXISTS `fund_requisition_approval_logs` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `fund_requisition_id` INT UNSIGNED NOT NULL,
                    `stage` VARCHAR(40) NOT NULL DEFAULT 'management',
                    `action` VARCHAR(30) NOT NULL DEFAULT 'approved',
                    `amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
                    `cumulative_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
                    `action_by` INT UNSIGNED NULL DEFAULT NULL,
                    `action_role_id` INT NULL DEFAULT NULL,
                    `note` TEXT NULL,
                    `is_backfilled` TINYINT(1) NOT NULL DEFAULT 0,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_fral_fr` (`fund_requisition_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {
            error_log('FundRequisitionApprovalLog::ensureTable error: ' . $e->getMessage());
        }
    }

    /**
     * Before writing the first log for a requisition that was already (partially) approved
     * before this log table existed, persist the existing approval as a backfilled row so the
     * breakdown stays accurate (e.g. 3,00,000 by A [backfilled] + 1,96,076 by B [new]).
     *
     * @param array $frRow fund_requisitions row BEFORE the new update is applied
     */
    public static function backfillIfMissing($db, array $frRow): void
    {
        self::ensureTable($db);
        $frId = (int)($frRow['id'] ?? 0);
        $prevApproved = (float)($frRow['approved_amount'] ?? 0);
        if ($frId <= 0 || $prevApproved <= 0 || empty($frRow['approved_by'])) {
            return;
        }
        if (!in_array($frRow['status'] ?? '', ['approved', 'partially_approved', 'paid', 'partially_paid'], true)) {
            return;
        }
        $res = $db->query("SELECT COUNT(*) AS c FROM fund_requisition_approval_logs WHERE fund_requisition_id = {$frId} AND stage = 'management'");
        $cnt = $res ? (int)($res->fetch_assoc()['c'] ?? 0) : 0;
        if ($cnt > 0) {
            return;
        }

        $userId = (int)$frRow['approved_by'];
        $roleId = null;
        $rRes = $db->query("SELECT role_id FROM users WHERE id = {$userId} LIMIT 1");
        if ($rRes && $rRow = $rRes->fetch_assoc()) {
            $roleId = (int)$rRow['role_id'];
        }
        $action = ($frRow['status'] === 'partially_approved') ? 'partially_approved' : 'approved';
        $note = $frRow['approval_note'] ?? null;
        $createdAt = !empty($frRow['approved_at']) ? $frRow['approved_at'] : date('Y-m-d H:i:s');

        $stmt = $db->prepare("
            INSERT INTO fund_requisition_approval_logs
                (fund_requisition_id, stage, action, amount, cumulative_amount, action_by, action_role_id, note, is_backfilled, created_at)
            VALUES (?, 'management', ?, ?, ?, ?, ?, ?, 1, ?)
        ");
        if ($stmt) {
            $stmt->bind_param('isddiiss', $frId, $action, $prevApproved, $prevApproved, $userId, $roleId, $note, $createdAt);
            $stmt->execute();
            $stmt->close();
        }
    }

    public static function log($db, int $frId, string $action, float $amount, float $cumulative, ?int $userId, ?string $note = null, string $stage = 'management'): bool
    {
        self::ensureTable($db);
        if ($frId <= 0) {
            return false;
        }
        $roleId = null;
        if (!empty($userId)) {
            $rRes = $db->query("SELECT role_id FROM users WHERE id = " . (int)$userId . " LIMIT 1");
            if ($rRes && $rRow = $rRes->fetch_assoc()) {
                $roleId = (int)$rRow['role_id'];
            }
        }
        $stmt = $db->prepare("
            INSERT INTO fund_requisition_approval_logs
                (fund_requisition_id, stage, action, amount, cumulative_amount, action_by, action_role_id, note, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        if (!$stmt) {
            error_log('FundRequisitionApprovalLog::log prepare error: ' . $db->error);
            return false;
        }
        $stmt->bind_param('issddiis', $frId, $stage, $action, $amount, $cumulative, $userId, $roleId, $note);
        $ok = $stmt->execute();
        $stmt->close();
        return (bool)$ok;
    }

    public static function stageLabelForRole(?int $roleId): string
    {
        return in_array((int)$roleId, [20, 32], true) ? 'Admin & Operation Head' : 'Management';
    }

    /**
     * Returns the ordered list of final-stage approvals (who approved how much & when).
     * Falls back to a single synthesized entry from the main table for legacy records.
     */
    public static function getManagementApprovals($db, int $frId, ?array $frRow = null): array
    {
        if (!$db || $frId <= 0) {
            return [];
        }
        self::ensureTable($db);

        $list = [];
        $sql = "
            SELECT l.*, u.full_name, u.role_id AS user_role_id,
                   (SELECT e.employee_id FROM employees e
                     WHERE e.user_id = u.id OR e.id = u.employee_id
                     ORDER BY (e.user_id = u.id) DESC LIMIT 1) AS emp_code,
                   COALESCE(
                       (SELECT des.name FROM employees e2
                          JOIN designations des ON des.id = e2.designation_id
                         WHERE e2.user_id = u.id OR e2.id = u.employee_id
                         ORDER BY (e2.user_id = u.id) DESC LIMIT 1),
                       (SELECT d3.name FROM designations d3 WHERE d3.id = u.designation_id LIMIT 1),
                       r.name
                   ) AS designation
            FROM fund_requisition_approval_logs l
            LEFT JOIN users u ON u.id = l.action_by
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE l.fund_requisition_id = ? AND l.stage = 'management'
              AND l.action IN ('approved', 'partially_approved')
            ORDER BY l.created_at ASC, l.id ASC
        ";
        $stmt = $db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $frId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $roleId = !empty($r['action_role_id']) ? (int)$r['action_role_id'] : (int)($r['user_role_id'] ?? 0);
                $list[] = [
                    'user_id'           => (int)$r['action_by'],
                    'name'              => $r['full_name'] ?: 'Management',
                    'emp_code'          => $r['emp_code'] ?? null,
                    'designation'       => $r['designation'] ?? null,
                    'role_id'           => $roleId,
                    'stage_label'       => self::stageLabelForRole($roleId),
                    'action'            => $r['action'],
                    'amount'            => (float)$r['amount'],
                    'cumulative_amount' => (float)$r['cumulative_amount'],
                    'note'              => $r['note'],
                    'created_at'        => $r['created_at'],
                    'is_legacy'         => !empty($r['is_backfilled']),
                ];
            }
            $stmt->close();
        }

        if (!empty($list)) {
            return $list;
        }

        // Legacy fallback: no logs yet -> synthesize from the main table
        if ($frRow === null || !array_key_exists('approved_by', $frRow)) {
            $fRes = $db->query("SELECT id, status, approved_by, approved_at, approved_amount, approval_note FROM fund_requisitions WHERE id = {$frId} LIMIT 1");
            $frRow = ($fRes && $fRes->num_rows > 0) ? $fRes->fetch_assoc() : null;
        }
        if (!$frRow || empty($frRow['approved_by']) || (float)($frRow['approved_amount'] ?? 0) <= 0) {
            return [];
        }
        if (!in_array($frRow['status'] ?? '', ['approved', 'partially_approved', 'paid', 'partially_paid'], true)) {
            return [];
        }

        $uid = (int)$frRow['approved_by'];
        $uRes = $db->query("
            SELECT u.full_name, u.role_id, e.employee_id AS emp_code, COALESCE(des.name, r.name) AS designation
            FROM users u
            LEFT JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
            LEFT JOIN designations des ON des.id = COALESCE(e.designation_id, u.designation_id)
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE u.id = {$uid} LIMIT 1
        ");
        $u = ($uRes && $uRes->num_rows > 0) ? $uRes->fetch_assoc() : [];
        $amt = (float)$frRow['approved_amount'];

        return [[
            'user_id'           => $uid,
            'name'              => $u['full_name'] ?? 'Management',
            'emp_code'          => $u['emp_code'] ?? null,
            'designation'       => $u['designation'] ?? null,
            'role_id'           => (int)($u['role_id'] ?? 0),
            'stage_label'       => self::stageLabelForRole((int)($u['role_id'] ?? 0)),
            'action'            => ($frRow['status'] === 'partially_approved') ? 'partially_approved' : 'approved',
            'amount'            => $amt,
            'cumulative_amount' => $amt,
            'note'              => $frRow['approval_note'] ?? null,
            'created_at'        => $frRow['approved_at'] ?? null,
            'is_legacy'         => true,
        ]];
    }
}
