<?php
class Con_QCInspectionItem {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getByInspectionId($inspection_id) {
        $stmt = $this->db->prepare("SELECT ii.*, c.checklist_item_name 
                                    FROM qc_inspection_items ii
                                    JOIN qc_checklists c ON ii.qc_checklist_id = c.id
                                    WHERE ii.qc_inspection_id = ?");
        $stmt->bind_param('i', $inspection_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function create($data) {
        $attachment = $data['attachment'] ?? null;
        $stmt = $this->db->prepare("INSERT INTO qc_inspection_items (qc_inspection_id, qc_checklist_id, is_passed, observation_note, attachment) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('iiiss', 
            $data['qc_inspection_id'], 
            $data['qc_checklist_id'], 
            $data['is_passed'], 
            $data['observation_note'],
            $attachment
        );
        return $stmt->execute();
    }
}
