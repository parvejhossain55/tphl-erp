<?php

class Con_PhaseTemplate {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get all active template phases ordered by phase_order.
     */
    public function getAll() {
        $result = $this->db->query("
            SELECT * FROM phase_template_defaults
            WHERE is_active = 1
            ORDER BY phase_order ASC
        ");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
