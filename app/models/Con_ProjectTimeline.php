<?php

class Con_ProjectTimeline {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get project timeline by project ID.
     */
    public function getByProject($project_id) {
        $stmt = $this->db->prepare("SELECT * FROM project_timelines WHERE project_id = ?");
        $stmt->bind_param('i', $project_id);
        if ($stmt->execute()) {
            return $stmt->get_result()->fetch_assoc();
        }
        return null;
    }

    /**
     * Find project timeline by ID.
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM project_timelines WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            return $stmt->get_result()->fetch_assoc();
        }
        return null;
    }

    /**
     * Create project timeline and update project workflow version.
     */
    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO project_timelines (
            project_id, project_code, project_type, project_category,
            handover_months, construction_months, post_construction_months,
            project_start_date, construction_start_date, construction_end_date,
            expected_handover_date, current_status, description, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param('isssiiissssssi',
            $data['project_id'],
            $data['project_code'],
            $data['project_type'],
            $data['project_category'],
            $data['handover_months'],
            $data['construction_months'],
            $data['post_construction_months'],
            $data['project_start_date'],
            $data['construction_start_date'],
            $data['construction_end_date'],
            $data['expected_handover_date'],
            $data['current_status'],
            $data['description'],
            $data['created_by']
        );

        if ($stmt->execute()) {
            // Automatically upgrade the project's workflow version to phase_based
            $this->updateProjectWorkflowVersion($data['project_id'], 'phase_based');
            return true;
        }
        return false;
    }

    /**
     * Update project timeline.
     */
    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE project_timelines SET
            project_code = ?,
            project_type = ?,
            project_category = ?,
            handover_months = ?,
            construction_months = ?,
            post_construction_months = ?,
            project_start_date = ?,
            construction_start_date = ?,
            construction_end_date = ?,
            expected_handover_date = ?,
            current_status = ?,
            description = ?
            WHERE id = ?");

        $stmt->bind_param('sssiisssssssi',
            $data['project_code'],
            $data['project_type'],
            $data['project_category'],
            $data['handover_months'],
            $data['construction_months'],
            $data['post_construction_months'],
            $data['project_start_date'],
            $data['construction_start_date'],
            $data['construction_end_date'],
            $data['expected_handover_date'],
            $data['current_status'],
            $data['description'],
            $id
        );

        return $stmt->execute();
    }

    /**
     * Update project's workflow version.
     */
    public function updateProjectWorkflowVersion($project_id, $version) {
        $stmt = $this->db->prepare("UPDATE projects SET workflow_version = ? WHERE id = ?");
        $stmt->bind_param('si', $version, $project_id);
        return $stmt->execute();
    }
}
