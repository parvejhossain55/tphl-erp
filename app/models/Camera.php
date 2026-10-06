<?php

class Camera extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Get all cameras, optionally filtered by project_id
     */
    public function getAll($projectId = null)
    {
        if ($projectId) {
            $stmt = $this->db->prepare("
                SELECT c.*, p.name AS project_name 
                FROM cameras c 
                LEFT JOIN projects p ON c.project_id = p.id 
                WHERE c.project_id = ?
                ORDER BY c.id DESC
            ");
            $stmt->bind_param("i", $projectId);
        } else {
            $stmt = $this->db->prepare("
                SELECT c.*, p.name AS project_name 
                FROM cameras c 
                LEFT JOIN projects p ON c.project_id = p.id 
                ORDER BY p.name ASC, c.id DESC
            ");
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Find camera by ID
     */
    public function find($id)
    {
        $stmt = $this->db->prepare("
            SELECT c.*, p.name AS project_name 
            FROM cameras c 
            LEFT JOIN projects p ON c.project_id = p.id 
            WHERE c.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Get cameras for a specific project
     */
    public function getByProjectId($projectId, $onlyActive = true)
    {
        $sql = "SELECT * FROM cameras WHERE project_id = ?";
        if ($onlyActive) {
            $sql .= " AND status = 'active'";
        }
        $sql .= " ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Create a new camera
     */
    public function create($data)
    {
        $projectId  = !empty($data['project_id']) ? (int)$data['project_id'] : null;
        $cameraName = !empty($data['camera_name']) ? trim($data['camera_name']) : null;
        $cameraSlug = !empty($data['camera_slug']) ? trim($data['camera_slug']) : null;
        $rtspUrl    = !empty($data['rtsp_url']) ? trim($data['rtsp_url']) : null;
        $webLink    = !empty($data['web_link']) ? trim($data['web_link']) : null;
        $status     = !empty($data['status']) ? trim($data['status']) : 'active';

        $stmt = $this->db->prepare("
            INSERT INTO cameras (project_id, camera_name, camera_slug, rtsp_url, web_link, status) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssss", $projectId, $cameraName, $cameraSlug, $rtspUrl, $webLink, $status);
        $success = $stmt->execute();
        $insertId = $this->db->insert_id;
        $stmt->close();
        return $success ? $insertId : false;
    }

    /**
     * Update an existing camera
     */
    public function update($id, $data)
    {
        $id         = (int)$id;
        $projectId  = !empty($data['project_id']) ? (int)$data['project_id'] : null;
        $cameraName = !empty($data['camera_name']) ? trim($data['camera_name']) : null;
        $cameraSlug = !empty($data['camera_slug']) ? trim($data['camera_slug']) : null;
        $rtspUrl    = !empty($data['rtsp_url']) ? trim($data['rtsp_url']) : null;
        $webLink    = !empty($data['web_link']) ? trim($data['web_link']) : null;
        $status     = !empty($data['status']) ? trim($data['status']) : 'active';

        $stmt = $this->db->prepare("
            UPDATE cameras 
            SET project_id = ?, camera_name = ?, camera_slug = ?, rtsp_url = ?, web_link = ?, status = ?
            WHERE id = ?
        ");
        $stmt->bind_param("isssssi", $projectId, $cameraName, $cameraSlug, $rtspUrl, $webLink, $status, $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Delete a camera
     */
    public function delete($id)
    {
        $id = (int)$id;
        $stmt = $this->db->prepare("DELETE FROM cameras WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
