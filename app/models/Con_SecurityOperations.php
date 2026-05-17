<?php
class Con_SecurityOperations
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // =========================================================================
    // SYSTEMS
    // =========================================================================

    public function getAllSystems()
    {
        $sql = "SELECT s.*, p.name as project_name, u.full_name as creator_name
                FROM con_security_systems s
                JOIN projects p ON s.project_id = p.id
                LEFT JOIN users u ON s.created_by = u.id
                ORDER BY s.id DESC";
        $result = $this->db->query($sql);
        $systems = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $systems[] = $row;
            }
        }
        return $systems;
    }

    public function createSystem($data)
    {
        $query = "INSERT INTO con_security_systems (project_id, system_type, vendor_id, installation_date, status, created_by)
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("isisss", $data['project_id'], $data['system_type'], $data['vendor_id'], $data['installation_date'], $data['status'], $data['created_by']);
        return $stmt->execute();
    }

    // =========================================================================
    // EQUIPMENTS
    // =========================================================================

    public function getAllEquipments()
    {
        $sql = "SELECT e.*, p.name as project_name, s.system_type as parent_system_type
                FROM con_security_equipments e
                JOIN projects p ON e.project_id = p.id
                JOIN con_security_systems s ON e.security_system_id = s.id
                ORDER BY e.id DESC";
        $result = $this->db->query($sql);
        $equipments = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $equipments[] = $row;
            }
        }
        return $equipments;
    }

    public function createEquipment($data)
    {
        $query = "INSERT INTO con_security_equipments (security_system_id, project_id, system_type, equipment_name, serial_number, mac_address, location_area, warranty_expiry, status, created_by)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("iisssssssi", $data['security_system_id'], $data['project_id'], $data['system_type'], $data['equipment_name'], $data['serial_number'], $data['mac_address'], $data['location_area'], $data['warranty_expiry'], $data['status'], $data['created_by']);
        return $stmt->execute();
    }

    // =========================================================================
    // LOGS
    // =========================================================================

    public function getAllLogs()
    {
        $sql = "SELECT l.*, p.name as project_name, u.full_name as operator_name, ru.full_name as resolved_by_name
                FROM con_security_daily_logs l
                JOIN projects p ON l.project_id = p.id
                LEFT JOIN users u ON l.operator_id = u.id
                LEFT JOIN users ru ON l.resolved_by = ru.id
                ORDER BY l.id DESC";
        $result = $this->db->query($sql);
        $logs = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
        }
        return $logs;
    }

    public function createLog($data)
    {
        $query = "INSERT INTO con_security_daily_logs (project_id, operator_id, log_date, checklist_data, condition_status, remarks)
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("iissss", $data['project_id'], $data['operator_id'], $data['log_date'], $data['checklist_data'], $data['condition_status'], $data['remarks']);
        return $stmt->execute();
    }

    public function resolveLog($log_id, $resolved_by)
    {
        $query = "UPDATE con_security_daily_logs SET status = 'Resolved', resolved_by = ?, resolved_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $resolved_by, $log_id);
        return $stmt->execute();
    }

    // =========================================================================
    // INCIDENTS
    // =========================================================================

    public function getAllIncidents()
    {
        $sql = "SELECT i.*, p.name as project_name, e.equipment_name, u.full_name as reported_by_name, ru.full_name as resolved_by_name
                FROM con_security_incidents i
                JOIN projects p ON i.project_id = p.id
                LEFT JOIN con_security_equipments e ON i.equipment_id = e.id
                LEFT JOIN users u ON i.reported_by = u.id
                LEFT JOIN users ru ON i.resolved_by = ru.id
                ORDER BY i.id DESC";
        $result = $this->db->query($sql);
        $incidents = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $incidents[] = $row;
            }
        }
        return $incidents;
    }

    public function createIncident($data)
    {
        $query = "INSERT INTO con_security_incidents (project_id, security_system_id, equipment_id, issue_title, description, priority, reported_by)
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("iiisssi", $data['project_id'], $data['security_system_id'], $data['equipment_id'], $data['issue_title'], $data['description'], $data['priority'], $data['reported_by']);
        return $stmt->execute();
    }

    public function resolveIncident($incident_id, $resolved_by)
    {
        $query = "UPDATE con_security_incidents SET status = 'Resolved', resolved_by = ?, resolved_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $resolved_by, $incident_id);
        return $stmt->execute();
    }
}
