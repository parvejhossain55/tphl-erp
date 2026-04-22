<?php
/**
 * Con_DPR Model
 * Table: daily_progress_reports
 *
 * Schema:
 * id, project_id, site_engineer_id, work_done, progress_percentage,
 * report_date, weather_condition, manpower_count, progress_image,
 * created_by, created_at
 */
class Con_DPR extends Model
{
    protected $table = 'daily_progress_reports';

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    // =========================================================================
    // READ
    // =========================================================================

    /** All DPR records with project + engineer name */
    public function getAll()
    {
        $sql = "SELECT dpr.*,
                       p.name       AS project_name,
                       se.full_name AS engineer_name,
                       cb.full_name AS created_by_name
                FROM {$this->table} dpr
                LEFT JOIN projects p  ON p.id  = dpr.project_id
                LEFT JOIN users    se ON se.id = dpr.site_engineer_id
                LEFT JOIN users    cb ON cb.id = dpr.created_by
                ORDER BY dpr.report_date DESC, dpr.created_at DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Single DPR record */
    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT dpr.*,
                    p.name       AS project_name,
                    se.full_name AS engineer_name,
                    cb.full_name AS created_by_name
             FROM {$this->table} dpr
             LEFT JOIN projects p  ON p.id  = dpr.project_id
             LEFT JOIN users    se ON se.id = dpr.site_engineer_id
             LEFT JOIN users    cb ON cb.id = dpr.created_by
             WHERE dpr.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /** DPR records by project */
    public function getByProject($projectId)
    {
        $stmt = $this->db->prepare(
            "SELECT dpr.*, se.full_name AS engineer_name
             FROM {$this->table} dpr
             LEFT JOIN users se ON se.id = dpr.site_engineer_id
             WHERE dpr.project_id = ?
             ORDER BY dpr.report_date DESC"
        );
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** DPR records by date range */
    public function getByDateRange($from, $to, $projectId = null)
    {
        if ($projectId) {
            $stmt = $this->db->prepare(
                "SELECT dpr.*, p.name AS project_name, se.full_name AS engineer_name
                 FROM {$this->table} dpr
                 LEFT JOIN projects p  ON p.id  = dpr.project_id
                 LEFT JOIN users    se ON se.id = dpr.site_engineer_id
                 WHERE dpr.report_date BETWEEN ? AND ?
                   AND dpr.project_id = ?
                 ORDER BY dpr.report_date DESC"
            );
            $stmt->bind_param('ssi', $from, $to, $projectId);
        } else {
            $stmt = $this->db->prepare(
                "SELECT dpr.*, p.name AS project_name, se.full_name AS engineer_name
                 FROM {$this->table} dpr
                 LEFT JOIN projects p  ON p.id  = dpr.project_id
                 LEFT JOIN users    se ON se.id = dpr.site_engineer_id
                 WHERE dpr.report_date BETWEEN ? AND ?
                 ORDER BY dpr.report_date DESC"
            );
            $stmt->bind_param('ss', $from, $to);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // WRITE
    // =========================================================================

    /** INSERT new DPR */
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table}
                (project_id, site_engineer_id, work_done,
                 progress_percentage, report_date,
                 weather_condition, manpower_count, progress_image, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'iisdssisi',
            $data['project_id'],
            $data['site_engineer_id'],
            $data['work_done'],
            $data['progress_percentage'],
            $data['report_date'],
            $data['weather_condition'],
            $data['manpower_count'],
            $data['progress_image'],
            $data['created_by']
        );
        $stmt->execute();
        return $stmt->insert_id;
    }

    /** DELETE (admin only) */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}
