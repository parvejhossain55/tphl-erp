<?php

class ProjectProgress extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Calculate total progress based on weights:
     * - structural_work: 35%
     * - remaining 6 categories: 65% distributed equally (65% / 6 = 10.833% each)
     */
    public static function computeTotalProgress($data)
    {
        $structural = isset($data['structural_work']) ? (float)$data['structural_work'] : 0.0;
        $plaster    = isset($data['plaster_work']) ? (float)$data['plaster_work'] : 0.0;
        $brick      = isset($data['brick_work']) ? (float)$data['brick_work'] : 0.0;
        $electrical = isset($data['electrical_work']) ? (float)$data['electrical_work'] : 0.0;
        $plumbing   = isset($data['plumbing_work']) ? (float)$data['plumbing_work'] : 0.0;
        $tiles      = isset($data['tiles_work']) ? (float)$data['tiles_work'] : 0.0;
        $paint      = isset($data['paint_work']) ? (float)$data['paint_work'] : 0.0;

        $othersSum = $plaster + $brick + $electrical + $plumbing + $tiles + $paint;
        $othersAvg = $othersSum / 6.0;

        $total = ($structural * 0.35) + ($othersAvg * 0.65);

        if ($total < 0) $total = 0;
        if ($total > 100) $total = 100;

        return round($total, 2);
    }

    /**
     * Get all progress records, optionally filtered by project_id
     */
    public function getAll($projectId = null)
    {
        if ($projectId) {
            $stmt = $this->db->prepare("
                SELECT pp.*, p.name AS project_name, l.name AS location_name
                FROM project_progress pp
                LEFT JOIN projects p ON pp.project_id = p.id
                LEFT JOIN locations l ON p.location_id = l.id
                WHERE pp.project_id = ?
                ORDER BY pp.id DESC
            ");
            $stmt->bind_param("i", $projectId);
        } else {
            $stmt = $this->db->prepare("
                SELECT pp.*, p.name AS project_name, l.name AS location_name
                FROM project_progress pp
                LEFT JOIN projects p ON pp.project_id = p.id
                LEFT JOIN locations l ON p.location_id = l.id
                ORDER BY p.name ASC, pp.id DESC
            ");
        }

        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Find single record by ID
     */
    public function find($id)
    {
        $stmt = $this->db->prepare("
            SELECT pp.*, p.name AS project_name, l.name AS location_name
            FROM project_progress pp
            LEFT JOIN projects p ON pp.project_id = p.id
            LEFT JOIN locations l ON p.location_id = l.id
            WHERE pp.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Find by project_id (latest entry)
     */
    public function findByProjectId($projectId)
    {
        $stmt = $this->db->prepare("
            SELECT pp.*, p.name AS project_name, l.name AS location_name
            FROM project_progress pp
            LEFT JOIN projects p ON pp.project_id = p.id
            LEFT JOIN locations l ON p.location_id = l.id
            WHERE pp.project_id = ?
            ORDER BY pp.id DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Create a new progress record
     */
    public function create($data)
    {
        $projectId         = !empty($data['project_id']) ? (int)$data['project_id'] : null;
        $lastUpdatedDate   = !empty($data['last_updated_date']) ? $data['last_updated_date'] : date('Y-m-d');
        $lastMonthTarget   = isset($data['last_month_target']) ? (float)$data['last_month_target'] : 0.0;
        $lastMonthAchieved = isset($data['last_month_achieved']) ? (float)$data['last_month_achieved'] : 0.0;
        $structural        = isset($data['structural_work']) ? (float)$data['structural_work'] : 0.0;
        $plaster           = isset($data['plaster_work']) ? (float)$data['plaster_work'] : 0.0;
        $brick             = isset($data['brick_work']) ? (float)$data['brick_work'] : 0.0;
        $electrical        = isset($data['electrical_work']) ? (float)$data['electrical_work'] : 0.0;
        $plumbing          = isset($data['plumbing_work']) ? (float)$data['plumbing_work'] : 0.0;
        $tiles             = isset($data['tiles_work']) ? (float)$data['tiles_work'] : 0.0;
        $paint             = isset($data['paint_work']) ? (float)$data['paint_work'] : 0.0;
        $totalProgress     = self::computeTotalProgress($data);
        $createdBy         = !empty($data['created_by']) ? (int)$data['created_by'] : null;

        $stmt = $this->db->prepare("
            INSERT INTO project_progress (
                project_id, last_updated_date, last_month_target, last_month_achieved,
                structural_work, plaster_work, brick_work, electrical_work, plumbing_work,
                tiles_work, paint_work, total_progress, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "isddddddddddi",
            $projectId, $lastUpdatedDate, $lastMonthTarget, $lastMonthAchieved,
            $structural, $plaster, $brick, $electrical, $plumbing,
            $tiles, $paint, $totalProgress, $createdBy
        );

        $success = $stmt->execute();
        $insertId = $this->db->insert_id;
        $stmt->close();

        return $success ? $insertId : false;
    }

    /**
     * Update an existing progress record
     */
    public function update($id, $data)
    {
        $id                = (int)$id;
        $projectId         = !empty($data['project_id']) ? (int)$data['project_id'] : null;
        $lastUpdatedDate   = !empty($data['last_updated_date']) ? $data['last_updated_date'] : date('Y-m-d');
        $lastMonthTarget   = isset($data['last_month_target']) ? (float)$data['last_month_target'] : 0.0;
        $lastMonthAchieved = isset($data['last_month_achieved']) ? (float)$data['last_month_achieved'] : 0.0;
        $structural        = isset($data['structural_work']) ? (float)$data['structural_work'] : 0.0;
        $plaster           = isset($data['plaster_work']) ? (float)$data['plaster_work'] : 0.0;
        $brick             = isset($data['brick_work']) ? (float)$data['brick_work'] : 0.0;
        $electrical        = isset($data['electrical_work']) ? (float)$data['electrical_work'] : 0.0;
        $plumbing          = isset($data['plumbing_work']) ? (float)$data['plumbing_work'] : 0.0;
        $tiles             = isset($data['tiles_work']) ? (float)$data['tiles_work'] : 0.0;
        $paint             = isset($data['paint_work']) ? (float)$data['paint_work'] : 0.0;
        $totalProgress     = self::computeTotalProgress($data);
        $updatedBy         = !empty($data['updated_by']) ? (int)$data['updated_by'] : null;

        $stmt = $this->db->prepare("
            UPDATE project_progress SET
                project_id = ?,
                last_updated_date = ?,
                last_month_target = ?,
                last_month_achieved = ?,
                structural_work = ?,
                plaster_work = ?,
                brick_work = ?,
                electrical_work = ?,
                plumbing_work = ?,
                tiles_work = ?,
                paint_work = ?,
                total_progress = ?,
                updated_by = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "isddddddddddii",
            $projectId, $lastUpdatedDate, $lastMonthTarget, $lastMonthAchieved,
            $structural, $plaster, $brick, $electrical, $plumbing,
            $tiles, $paint, $totalProgress, $updatedBy, $id
        );

        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Delete a progress record
     */
    public function delete($id)
    {
        $id = (int)$id;
        $stmt = $this->db->prepare("DELETE FROM project_progress WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }
}
