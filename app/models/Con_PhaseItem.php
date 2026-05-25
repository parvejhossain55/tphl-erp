<?php

class Con_PhaseItem {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ── Get all items for a phase ────────────────────────────────────────────
    public function getByPhase(int $phase_id): array {
        $stmt = $this->db->prepare("
            SELECT ppi.*,
                   dt.name AS design_type_name,
                   wt.work_type_name
            FROM project_phase_items ppi
            LEFT JOIN design_types dt ON dt.id = ppi.design_type_id
            LEFT JOIN work_types   wt ON wt.id = ppi.work_type_id
            WHERE ppi.phase_id = ?
            ORDER BY ppi.item_order ASC, ppi.id ASC
        ");
        $stmt->bind_param('i', $phase_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // ── Get items for all phases of a project (grouped) ─────────────────────
    public function getByProject(int $project_id): array {
        $stmt = $this->db->prepare("
            SELECT ppi.*,
                   dt.name AS design_type_name
            FROM project_phase_items ppi
            INNER JOIN project_phases pp ON pp.id = ppi.phase_id
            LEFT JOIN  design_types   dt ON dt.id = ppi.design_type_id
            WHERE pp.project_id = ?
            ORDER BY pp.phase_order ASC, ppi.item_order ASC
        ");
        $stmt->bind_param('i', $project_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // ── Find single item ─────────────────────────────────────────────────────
    public function find(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM project_phase_items WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    // ── Check if items exist for a phase ─────────────────────────────────────
    public function existsForPhase(int $phase_id): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM project_phase_items WHERE phase_id = ?");
        $stmt->bind_param('i', $phase_id);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['cnt'] > 0;
    }

    // ── Insert from template row ─────────────────────────────────────────────
    public function insertFromTemplate(int $phase_id, array $tpl): bool {
        $stmt = $this->db->prepare("
            INSERT INTO project_phase_items
                (phase_id, item_name, item_order, weight_pct, design_type_id, work_type_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $wtId = !empty($tpl['work_type_id']) ? (int)$tpl['work_type_id'] : null;
        $stmt->bind_param('isidii',
            $phase_id,
            $tpl['item_name'],
            $tpl['item_order'],
            $tpl['weight_pct'],
            $tpl['design_type_id'],
            $wtId
        );
        return $stmt->execute();
    }

    // ── Create a custom item ─────────────────────────────────────────────────
    public function create(array $data): bool {
        $stmt = $this->db->prepare("
            INSERT INTO project_phase_items
                (phase_id, item_name, item_order, weight_pct,
                 planned_progress, actual_progress, design_type_id, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $nextOrder = $this->nextOrder((int)$data['phase_id']);
        $stmt->bind_param('isiddis',
            $data['phase_id'],
            $data['item_name'],
            $nextOrder,
            $data['weight_pct']       ?? 0.00,
            $data['planned_progress'] ?? 0.00,
            $data['actual_progress']  ?? 0.00,
            $data['design_type_id']   ?? null,
            $data['status']           ?? 'Not Started'
        );
        return $stmt->execute();
    }

    // ── Batch update items for a phase ───────────────────────────────────────
    public function batchUpdate(array $items): bool {
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE project_phase_items SET
                    item_name        = ?,
                    weight_pct       = ?,
                    actual_progress  = ?,
                    planned_progress = ?,
                    status           = ?,
                    work_type_id     = ?,
                    design_type_id   = ?
                WHERE id = ?
            ");
            foreach ($items as $item) {
                $id      = (int)$item['id'];
                $name    = trim($item['item_name']);
                $wpct    = (float)($item['weight_pct']       ?? 0);
                $actual  = (float)($item['actual_progress']  ?? 0);
                $planned = (float)($item['planned_progress'] ?? 0);
                $status  = $item['status'] ?? 'Not Started';
                $wtId    = ($item['work_type_id'] > 0) ? (int)$item['work_type_id'] : null;
                $dtId    = ($item['design_type_id'] > 0) ? (int)$item['design_type_id'] : null;
                $stmt->bind_param('sddisiii', $name, $wpct, $actual, $planned, $status, $wtId, $dtId, $id);
                $stmt->execute();
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    // ── Delete an item ───────────────────────────────────────────────────────
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM project_phase_items WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    // ── Next item order for a phase ──────────────────────────────────────────
    public function nextOrder(int $phase_id): int {
        $stmt = $this->db->prepare("SELECT COALESCE(MAX(item_order),0)+1 AS n FROM project_phase_items WHERE phase_id = ?");
        $stmt->bind_param('i', $phase_id);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['n'];
    }

    // ── Sum of weight_pct for a phase ────────────────────────────────────────
    public function totalWeight(int $phase_id): float {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(weight_pct),0) AS t FROM project_phase_items WHERE phase_id = ?");
        $stmt->bind_param('i', $phase_id);
        $stmt->execute();
        return (float)$stmt->get_result()->fetch_assoc()['t'];
    }

    // ── Recalculate phase actual_progress from items ─────────────────────────
    // Returns calculated progress (0-100)
    public function recalcPhaseProgress(int $phase_id): float {
        $items = $this->getByPhase($phase_id);
        if (empty($items)) return 0.0;

        $totalWeight = array_sum(array_column($items, 'weight_pct'));
        if ($totalWeight <= 0) return 0.0;

        $progress = 0.0;
        foreach ($items as $item) {
            $progress += ($item['weight_pct'] / $totalWeight) * (float)$item['actual_progress'];
        }
        return round($progress, 2);
    }

    // ── Persist recalculated phase progress back to project_phases ───────────
    public function syncPhaseProgress(int $phase_id): bool {
        $progress = $this->recalcPhaseProgress($phase_id);
        $stmt = $this->db->prepare("UPDATE project_phases SET actual_progress = ? WHERE id = ?");
        $stmt->bind_param('di', $progress, $phase_id);
        return $stmt->execute();
    }

    // ── Get template items for a phase_name ─────────────────────────────────
    public function getTemplateByPhaseName(string $phase_name): array {
        $stmt = $this->db->prepare("
            SELECT * FROM phase_item_template_defaults
            WHERE phase_name = ?
            ORDER BY item_order ASC
        ");
        $stmt->bind_param('s', $phase_name);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
