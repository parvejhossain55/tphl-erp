<?php

class ProjectCategory
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /* ── All Categories ─────────────────────────────────── */
    public function getAll(): array
    {
        $sql = "SELECT pc.*, u.full_name AS created_by_name
                FROM project_categories pc
                LEFT JOIN users u ON pc.created_by = u.id
                ORDER BY pc.name ASC";
        $result = $this->db->query($sql);
        if (!$result) return [];
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /* ── Single Category ────────────────────────────────── */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM project_categories WHERE id = ? LIMIT 1"
        );
        if (!$stmt) return null;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /* ── Create ─────────────────────────────────────────── */
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO project_categories (name, incentive_percentage, created_by)
             VALUES (?, ?, ?)"
        );
        if (!$stmt) return false;
        $stmt->bind_param(
            'sdi',
            $data['name'],
            $data['incentive_percentage'],
            $data['created_by']
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /* ── Update ─────────────────────────────────────────── */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE project_categories
             SET name = ?, incentive_percentage = ?
             WHERE id = ?"
        );
        if (!$stmt) return false;
        $stmt->bind_param(
            'sdi',
            $data['name'],
            $data['incentive_percentage'],
            $id
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /* ── Delete ─────────────────────────────────────────── */
    public function delete(int $id): bool
    {
        // Nullify category_id in projects before deleting
        $nullify = $this->db->prepare(
            "UPDATE projects SET category_id = NULL, incentive_percentage = NULL WHERE category_id = ?"
        );
        if ($nullify) {
            $nullify->bind_param('i', $id);
            $nullify->execute();
            $nullify->close();
        }

        $stmt = $this->db->prepare("DELETE FROM project_categories WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
