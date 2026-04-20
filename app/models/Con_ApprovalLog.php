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

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
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
        $sql = "INSERT INTO {$this->table}
                (ref_table, ref_id, step_name, actioned_by, status, remarks, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'siisis',
            $data['ref_table'],
            $data['ref_id'],
            $data['step_name'],
            $data['actioned_by'],
            $data['status'],
            $data['remarks']
        );
        return $stmt->execute();
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
