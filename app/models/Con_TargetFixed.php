<?php
/**
 * Con_TargetFixed Model
 * Table: target_fixed
 *
 * Schema:
 * id, project_id, work_plan_id, target_title, user_id,
 * year, month, week_number, target_percentage, gain_percentage,
 * photo_link, field_test_details, lab_test_result,
 * approved_by_site_eng, approved_by_qc, approved_by_pc,
 * approved_by_pd, approved_by_manager,
 * qc_reviewed_by_id, qc_reviewed_at,
 * is_lab_test, lab_test_date, target_details,
 * created_at, updated_at
 */
class Con_TargetFixed extends Model
{
    protected $table = 'target_fixed';

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    // =========================================================================
    // READ
    // =========================================================================

    /** All targets with project, work_plan, design_type, user info */
    public function getAll()
    {
        $sql = "SELECT tf.*,
                       p.name           AS project_name,
                       wp.work_description AS plan_description,
                       wp.year          AS plan_year,
                       wp.month         AS plan_month,
                       wt.work_type_name AS work_type_name,
                       dt.name          AS design_type_name,
                       u.full_name      AS created_by_name,
                       qc.full_name     AS qc_reviewed_by_name
                FROM {$this->table} tf
                LEFT JOIN projects              p  ON p.id  = tf.project_id
                LEFT JOIN project_working_plans wp ON wp.id = tf.work_plan_id
                LEFT JOIN work_types            wt ON wt.id = wp.work_type_id
                LEFT JOIN design_types          dt ON dt.id = wt.design_type_id
                LEFT JOIN users                 u  ON u.id  = tf.user_id
                LEFT JOIN users                 qc ON qc.id = tf.qc_reviewed_by_id
                ORDER BY tf.year DESC, tf.month DESC, tf.week_number ASC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Single target with full relations */
    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT tf.*,
                    p.name           AS project_name,
                    wp.work_description AS plan_description,
                    wp.year          AS plan_year,
                    wp.month         AS plan_month,
                    wp.estimated_cost AS plan_estimated_cost,
                    wt.work_type_name AS work_type_name,
                    dt.name          AS design_type_name,
                    u.full_name      AS created_by_name,
                    qc.full_name     AS qc_reviewed_by_name
             FROM {$this->table} tf
             LEFT JOIN projects              p  ON p.id  = tf.project_id
             LEFT JOIN project_working_plans wp ON wp.id = tf.work_plan_id
             LEFT JOIN work_types            wt ON wt.id = wp.work_type_id
             LEFT JOIN design_types          dt ON dt.id = wt.design_type_id
             LEFT JOIN users                 u  ON u.id  = tf.user_id
             LEFT JOIN users                 qc ON qc.id = tf.qc_reviewed_by_id
             WHERE tf.id = ?"
        );
        if (!$stmt) {
            error_log('Con_TargetFixed::find prepare failed: ' . $this->db->error);
            return null;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /** Targets by project */
    public function getByProject($projectId)
    {
        $stmt = $this->db->prepare(
            "SELECT tf.*,
                    wp.work_description AS plan_description,
                    wt.work_type_name   AS work_type_name,
                    dt.name             AS design_type_name
             FROM {$this->table} tf
             LEFT JOIN project_working_plans wp ON wp.id = tf.work_plan_id
             LEFT JOIN work_types            wt ON wt.id = wp.work_type_id
             LEFT JOIN design_types          dt ON dt.id = wt.design_type_id
             WHERE tf.project_id = ?
             ORDER BY tf.year DESC, tf.month DESC"
        );
        if (!$stmt) {
            error_log('Con_TargetFixed::getByProject prepare failed: ' . $this->db->error);
            return [];
        }
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Targets by working plan */
    public function getByWorkPlan($workPlanId)
    {
        $stmt = $this->db->prepare(
            "SELECT tf.*, p.name AS project_name
             FROM {$this->table} tf
             LEFT JOIN projects p ON p.id = tf.project_id
             WHERE tf.work_plan_id = ?
             ORDER BY tf.year DESC, tf.month DESC, tf.week_number ASC"
        );
        $stmt->bind_param('i', $workPlanId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // WRITE
    // =========================================================================

    /** INSERT new target */
    public function create($data)
    {
        $isLabTest   = (int)($data['is_lab_test']   ?? 0);
        $labTestDate = $isLabTest ? ($data['lab_test_date'] ?? null) : null;
        $details     = $data['target_details'] ?? null;

        $sql = "INSERT INTO {$this->table}
                (project_id, work_plan_id, target_title, user_id,
                 year, month, week_number, target_percentage,
                 is_lab_test, lab_test_date, target_details,
                 gain_percentage, photo_link, field_test_details,
                 lab_test_result, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, NULL, NULL, NULL, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'iisiiiidiss',
            $data['project_id'],
            $data['work_plan_id'],
            $data['target_title'],
            $data['user_id'],
            $data['year'],
            $data['month'],
            $data['week_number'],
            $data['target_percentage'],
            $isLabTest,
            $labTestDate,
            $details
        );
        $stmt->execute();
        return $stmt->insert_id;
    }

    /** UPDATE target basic info */
    public function update($id, $data)
    {
        $isLabTest   = (int)($data['is_lab_test']   ?? 0);
        $labTestDate = $isLabTest ? ($data['lab_test_date'] ?? null) : null;
        $details     = $data['target_details'] ?? null;

        $sql = "UPDATE {$this->table}
                SET project_id        = ?,
                    work_plan_id      = ?,
                    target_title      = ?,
                    year              = ?,
                    month             = ?,
                    week_number       = ?,
                    target_percentage = ?,
                    is_lab_test       = ?,
                    lab_test_date     = ?,
                    target_details    = ?,
                    updated_at        = NOW()
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'iisiiiidissi',
            $data['project_id'],
            $data['work_plan_id'],
            $data['target_title'],
            $data['year'],
            $data['month'],
            $data['week_number'],
            $data['target_percentage'],
            $isLabTest,
            $labTestDate,
            $details,
            $id
        );
        return $stmt->execute();
    }

    /** UPDATE gain_percentage + photo (QC updates this after field work) */
    public function updateGain($id, $gain, $photo = null, $fieldDetails = null, $labResult = null)
    {
        $sql = "UPDATE {$this->table}
                SET gain_percentage   = ?,
                    photo_link        = ?,
                    field_test_details= ?,
                    lab_test_result   = ?,
                    updated_at        = NOW()
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('dsssi', $gain, $photo, $fieldDetails, $labResult, $id);
        return $stmt->execute();
    }

    /**
     * Approve a specific step
     * $step values: 'site_eng' | 'qc' | 'pc' | 'pd' | 'manager'
     */
    public function approveStep($id, $step, $userId)
    {
        $colMap = [
            'site_eng' => 'approved_by_site_eng',
            'qc'       => 'approved_by_qc',
            'pc'       => 'approved_by_pc',
            'pd'       => 'approved_by_pd',
            'manager'  => 'approved_by_manager',
        ];

        if (!isset($colMap[$step])) return false;

        $col = $colMap[$step];

        // If QC step, also record qc_reviewed_by_id and qc_reviewed_at
        if ($step === 'qc') {
            $sql = "UPDATE {$this->table}
                    SET {$col} = 1,
                        qc_reviewed_by_id = ?,
                        qc_reviewed_at    = NOW(),
                        updated_at        = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii', $userId, $id);
        } else {
            $sql = "UPDATE {$this->table}
                    SET {$col} = 1, updated_at = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
        }
        return $stmt->execute();
    }

    /** Check if target is fully approved by PM (role 22) */
    public function isFullyApproved($id)
    {
        $stmt = $this->db->prepare(
            "SELECT approved_by_manager AS is_approved
             FROM {$this->table} WHERE id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row && $row['is_approved'] == 1;
    }

    /** DELETE — only if no approvals */
    public function delete($id)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table}
             WHERE id = ?
               AND approved_by_manager = 0"
        );
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}
