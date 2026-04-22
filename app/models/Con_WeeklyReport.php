<?php
/**
 * Con_WeeklyReport Model
 * Table: weekly_progress_report
 *
 * Schema:
 *  id, project_id, site_engineer_id, work_plan_id, week_no, target_id,
 *  work_done, progress_percentage, report_date, progress_image,
 *  weather_condition, manpower_count, created_by, created_at
 */
class Con_WeeklyReport extends Model
{
    protected $table = 'weekly_progress_report';

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    // =========================================================================
    // READ
    // =========================================================================

    /** All reports with project + engineer + work_plan + target info */
    public function getAll()
    {
        $sql = "SELECT r.*,
                       p.name         AS project_name,
                       se.full_name   AS engineer_name,
                       wp.work_description AS work_plan_title,
                       t.target_title AS target_title,
                       t.year         AS target_year,
                       t.month        AS target_month,
                       cb.full_name   AS created_by_name
                FROM {$this->table} r
                LEFT JOIN projects             p  ON p.id  = r.project_id
                LEFT JOIN users               se  ON se.id = r.site_engineer_id
                LEFT JOIN project_working_plans wp ON wp.id = r.work_plan_id
                LEFT JOIN target_fixed         t  ON t.id  = r.target_id
                LEFT JOIN users               cb  ON cb.id = r.created_by
                ORDER BY r.created_at DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Single report */
    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT r.*,
                    p.name         AS project_name,
                    se.full_name   AS engineer_name,
                    wp.work_description AS work_plan_title,
                    t.target_title AS target_title,
                    t.year         AS target_year,
                    t.month        AS target_month,
                    cb.full_name   AS created_by_name
             FROM {$this->table} r
             LEFT JOIN projects             p  ON p.id  = r.project_id
             LEFT JOIN users               se  ON se.id = r.site_engineer_id
             LEFT JOIN project_working_plans wp ON wp.id = r.work_plan_id
             LEFT JOIN target_fixed         t  ON t.id  = r.target_id
             LEFT JOIN users               cb  ON cb.id = r.created_by
             WHERE r.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /** Reports by project */
    public function getByProject($projectId)
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, se.full_name AS engineer_name,
                    wp.work_description AS work_plan_title,
                    t.target_title
             FROM {$this->table} r
             LEFT JOIN users               se  ON se.id = r.site_engineer_id
             LEFT JOIN project_working_plans wp ON wp.id = r.work_plan_id
             LEFT JOIN target_fixed         t  ON t.id  = r.target_id
             WHERE r.project_id = ?
             ORDER BY r.created_at DESC"
        );
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // AJAX CASCADES
    // =========================================================================

    /** Working plans for a project (for dropdown cascade) */
    public function getWorkPlansByProject($projectId)
    {
        $stmt = $this->db->prepare(
            "SELECT id, work_description, year, month
             FROM project_working_plans
             WHERE project_id = ?
             ORDER BY year DESC, month DESC"
        );
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Targets for a work_plan (returns year, month, week_number list) */
    public function getTargetsByWorkPlan($workPlanId)
    {
        $stmt = $this->db->prepare(
            "SELECT id, target_title, year, month, week_number
             FROM target_fixed
             WHERE work_plan_id = ?
             ORDER BY year DESC, month DESC, week_number ASC"
        );
        $stmt->bind_param('i', $workPlanId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // WRITE
    // =========================================================================

    /** INSERT new weekly report */
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table}
                (project_id, site_engineer_id, work_plan_id, week_no, target_id,
                 work_done, progress_percentage, report_date, progress_image,
                 weather_condition, manpower_count, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'iiiiisdsssii',
            $data['project_id'],
            $data['site_engineer_id'],
            $data['work_plan_id'],
            $data['week_no'],
            $data['target_id'],
            $data['work_done'],
            $data['progress_percentage'],
            $data['report_date'],
            $data['progress_image'],
            $data['weather_condition'],
            $data['manpower_count'],
            $data['created_by']
        );
        $stmt->execute();
        return $stmt->insert_id;
    }

    /** DELETE */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}
