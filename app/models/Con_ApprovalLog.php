<?php
/**
 * Con_ApprovalLog Model
 * Table: con_approval_logs
 *
 * Schema:
 * id, ref_table, ref_id, step_name, actioned_by,
 * status (approved|rejected|noted), remarks, created_at
 */
class Con_ApprovalLog extends Model
{
    protected $table = 'con_approval_logs';
    private static $schemaChecked = false;

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    /**
     * Ensure con_approval_logs table and extended attribution columns exist.
     */
    public function ensureSchema()
    {
        if (self::$schemaChecked || !$this->db) {
            return;
        }
        self::$schemaChecked = true;

        $this->db->query("CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ref_table VARCHAR(100) NOT NULL,
            ref_id INT NOT NULL,
            project_id INT NULL,
            step_name VARCHAR(100) NOT NULL,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            actioned_by INT NULL,
            actioned_role_id INT NULL,
            on_behalf_of VARCHAR(100) NULL,
            approval_label VARCHAR(255) NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'approved',
            remarks TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ref (ref_table, ref_id),
            INDEX idx_project (project_id),
            INDEX idx_actioned_by (actioned_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $existingCols = [];
        $colRes = $this->db->query("SHOW COLUMNS FROM {$this->table}");
        if ($colRes) {
            while ($row = $colRes->fetch_assoc()) {
                $existingCols[$row['Field']] = true;
            }
        }

        $alterCols = [
            'project_id'       => "ADD COLUMN project_id INT NULL AFTER ref_id",
            'amount'           => "ADD COLUMN amount DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER step_name",
            'actioned_role_id' => "ADD COLUMN actioned_role_id INT NULL AFTER actioned_by",
            'on_behalf_of'     => "ADD COLUMN on_behalf_of VARCHAR(100) NULL AFTER actioned_role_id",
            'approval_label'   => "ADD COLUMN approval_label VARCHAR(255) NULL AFTER on_behalf_of",
        ];

        foreach ($alterCols as $col => $ddl) {
            if (!isset($existingCols[$col])) {
                $this->db->query("ALTER TABLE {$this->table} {$ddl}");
            }
        }
    }

    /**
     * Build consistent role attribution for Dept. Head (PD, role 22),
     * Project Coordinator (PC, role 15 acting on behalf of Dept. Head), and Management.
     */
    public static function formatApprovalAttribution($roleId, $userName, $status = 'approved')
    {
        $roleId = (int)$roleId;
        $cleanName = trim((string)$userName) !== '' ? trim((string)$userName) : 'User';
        $verb = ($status === 'rejected') ? 'Rejected' : (($status === 'noted') ? 'Noted' : 'Approved');

        if ($roleId === 22) {
            return [
                'role_label'     => 'Dept. Head (PD)',
                'on_behalf_of'   => null,
                'approval_label' => "{$verb} by Dept. Head ({$cleanName})",
            ];
        }

        if ($roleId === 15) {
            return [
                'role_label'     => 'PC (On behalf of Dept. Head)',
                'on_behalf_of'   => 'Dept. Head',
                'approval_label' => "{$verb} by PC {$cleanName} (On behalf of Dept. Head)",
            ];
        }

        if (in_array($roleId, [1, 3, 33, 34, 35], true)) {
            return [
                'role_label'     => 'Management',
                'on_behalf_of'   => null,
                'approval_label' => "{$verb} by Management ({$cleanName})",
            ];
        }

        if (in_array($roleId, [20, 32], true)) {
            return [
                'role_label'     => 'Operation Head',
                'on_behalf_of'   => null,
                'approval_label' => "{$verb} by Operation Head ({$cleanName})",
            ];
        }

        return [
            'role_label'     => 'Approver',
            'on_behalf_of'   => null,
            'approval_label' => "{$verb} by {$cleanName}",
        ];
    }

    /**
     * Get approval history for a specific record
     */
    public function getByRef($refTable, $refId)
    {
        $stmt = $this->db->prepare(
            "SELECT cal.*,
                    u.full_name AS actioned_by_name,
                    u.profile_pic
             FROM {$this->table} cal
             LEFT JOIN users u ON u.id = cal.actioned_by
             WHERE cal.ref_table = ? AND cal.ref_id = ?
             ORDER BY cal.created_at ASC"
        );
        $stmt->bind_param('si', $refTable, $refId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Log an approval action
     * Called from any controller that has an approval step
     */
    public function logAction($data)
    {
        $this->ensureSchema();

        $sql = "INSERT INTO {$this->table}
                (ref_table, ref_id, project_id, step_name, amount, actioned_by, actioned_role_id, on_behalf_of, approval_label, status, remarks, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $refTable       = (string)($data['ref_table'] ?? '');
        $refId          = (int)($data['ref_id'] ?? 0);
        $projectId      = isset($data['project_id']) && $data['project_id'] !== null ? (int)$data['project_id'] : null;
        $stepName       = (string)($data['step_name'] ?? 'Dept. Head Approval');
        $amount         = (float)($data['amount'] ?? 0);
        $actionedBy     = (int)($data['actioned_by'] ?? 0);
        $actionedRoleId = isset($data['actioned_role_id']) ? (int)$data['actioned_role_id'] : null;
        $onBehalfOf     = $data['on_behalf_of'] ?? null;
        $approvalLabel  = $data['approval_label'] ?? null;
        $status         = (string)($data['status'] ?? 'approved');
        $remarks        = (string)($data['remarks'] ?? '');

        $stmt->bind_param(
            'siisdiissss',
            $refTable,
            $refId,
            $projectId,
            $stepName,
            $amount,
            $actionedBy,
            $actionedRoleId,
            $onBehalfOf,
            $approvalLabel,
            $status,
            $remarks
        );
        return $stmt->execute();
    }

    /**
     * Convenience method for Phase2 controllers to record construction approvals
     * with automatic PD (Dept. Head) vs. PC (on behalf of Dept. Head) attribution.
     */
    public function logConstructionApproval($refTable, $refId, $stepName, $status = 'approved', $projectId = null, $amount = 0.0, $remarks = '', $userId = null, $roleId = null, $userName = null)
    {
        $userId   = $userId ?? (int)($_SESSION['user']['id'] ?? 0);
        $roleId   = $roleId ?? (int)($_SESSION['user']['role_id'] ?? 0);
        $userName = $userName ?? ($_SESSION['user']['full_name'] ?? 'User');

        $attr = self::formatApprovalAttribution($roleId, $userName, $status);

        return $this->logAction([
            'ref_table'        => $refTable,
            'ref_id'           => $refId,
            'project_id'       => $projectId,
            'step_name'        => $stepName,
            'amount'           => $amount,
            'actioned_by'      => $userId,
            'actioned_role_id' => $roleId,
            'on_behalf_of'     => $attr['on_behalf_of'],
            'approval_label'   => $attr['approval_label'],
            'status'           => $status,
            'remarks'          => $remarks,
        ]);
    }

    /**
     * Get all approval logs — for admin audit view
     */
    public function getAll()
    {
        $sql = "SELECT cal.*,
                       u.full_name AS actioned_by_name
                FROM {$this->table} cal
                LEFT JOIN users u ON u.id = cal.actioned_by
                ORDER BY cal.created_at DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
