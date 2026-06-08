<?php

class Con_ProjectPhase {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get all phases for a project (ordered by phase_order).
     */
    public function getByProject($project_id) {
        $stmt = $this->db->prepare("
            SELECT pp.*,
                   (SELECT COUNT(*) FROM project_drawings pd WHERE pd.phase_id = pp.id) AS drawing_count
            FROM project_phases pp
            WHERE pp.project_id = ?
            ORDER BY pp.phase_order ASC
        ");
        $stmt->bind_param('i', $project_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Find a single phase by ID.
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM project_phases WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Insert a single phase from template data.
     */
    public function insertFromTemplate($project_id, $template, $construction_start = null, $construction_end = null) {
        $stmt = $this->db->prepare("
            INSERT INTO project_phases
                (project_id, phase_name, phase_order, weight_percentage,
                 planned_start_date, planned_end_date, status)
            VALUES (?, ?, ?, ?, ?, ?, 'Not Started')
        ");
        $start = $construction_start ?: null;
        $end   = $construction_end   ?: null;
        $stmt->bind_param('isidss',
            $project_id,
            $template['phase_name'],
            $template['phase_order'],
            $template['default_weight_percentage'],
            $start,
            $end
        );
        return $stmt->execute();
    }

    /**
     * Insert a custom new phase row.
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO project_phases
                (project_id, phase_name, phase_order, weight_percentage,
                 planned_start_date, planned_end_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $start  = $data['planned_start_date'] ?: null;
        $end    = $data['planned_end_date']   ?: null;
        $status = $data['status']             ?? 'Not Started';
        $stmt->bind_param('isidsss',
            $data['project_id'],
            $data['phase_name'],
            $data['phase_order'],
            $data['weight_percentage'],
            $start,
            $end,
            $status
        );
        return $stmt->execute();
    }

    /**
     * Batch save all phases for a project (replace rows by ID).
     */
    public function batchUpdate($phases, $project_id) {
        $this->db->begin_transaction();
        try {
            // Reset drawings phase links for this project
            $clearStmt = $this->db->prepare("UPDATE project_drawings SET phase_id = NULL WHERE project_id = ?");
            $clearStmt->bind_param('i', $project_id);
            $clearStmt->execute();

            $drawStmt = $this->db->prepare("UPDATE project_drawings SET phase_id = ? WHERE id = ? AND project_id = ?");

            $stmt = $this->db->prepare("
                UPDATE project_phases SET
                    phase_name          = ?,
                    phase_order         = ?,
                    weight_percentage   = ?,
                    planned_start_date  = ?,
                    planned_end_date    = ?,
                    status              = ?
                WHERE id = ?
            ");
            foreach ($phases as $p) {
                $id     = (int)$p['id'];
                $name   = trim($p['phase_name']);
                $order  = (int)$p['phase_order'];
                $weight = (float)$p['weight_percentage'];
                $start  = $p['planned_start_date'] ?: null;
                $end    = $p['planned_end_date']   ?: null;
                $status = $p['status']             ?? 'Not Started';
                $stmt->bind_param('sidsssi', $name, $order, $weight, $start, $end, $status, $id);
                $stmt->execute();

                if (!empty($p['drawing_id'])) {
                    $drawingId = (int)$p['drawing_id'];
                    $drawStmt->bind_param('iii', $id, $drawingId, $project_id);
                    $drawStmt->execute();
                }
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Delete a single phase by ID.
     * Cannot delete if it has linked monthly targets.
     */
    public function delete($id) {
        // Check for linked targets
        $check = $this->db->prepare("SELECT COUNT(*) AS cnt FROM phase_monthly_targets WHERE phase_id = ?");
        $check->bind_param('i', $id);
        $check->execute();
        $cnt = (int)$check->get_result()->fetch_assoc()['cnt'];
        if ($cnt > 0) return false; // has linked data

        $stmt = $this->db->prepare("DELETE FROM project_phases WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    /**
     * Recalculate next available phase_order for a project.
     */
    public function nextOrder($project_id) {
        $stmt = $this->db->prepare("SELECT COALESCE(MAX(phase_order),0)+1 AS next_order FROM project_phases WHERE project_id = ?");
        $stmt->bind_param('i', $project_id);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['next_order'];
    }

    /**
     * Sum of weight percentages for a project.
     */
    public function totalWeight($project_id) {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(weight_percentage),0) AS total FROM project_phases WHERE project_id = ?");
        $stmt->bind_param('i', $project_id);
        $stmt->execute();
        return (float)$stmt->get_result()->fetch_assoc()['total'];
    }
}
