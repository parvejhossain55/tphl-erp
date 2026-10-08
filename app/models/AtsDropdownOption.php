<?php

class AtsDropdownOption extends Model
{
    protected $table = 'ats_dropdown_options';

    public function __construct($db = null)
    {
        parent::__construct($db);
    }

    /**
     * Get all active options grouped by category
     */
    public function getAllActiveGrouped(): array
    {
        $stmt = $this->db->prepare("
            SELECT category, option_value, display_order, id
            FROM {$this->table}
            WHERE is_active = 1
            ORDER BY category ASC, display_order ASC, option_value ASC
        ");

        if (!$stmt) return [];
        $stmt->execute();
        $result = $stmt->get_result();
        
        $grouped = [
            'department' => [],
            'position' => [],
            'source' => [],
            'invitation_status' => [],
            'interview_location' => []
        ];

        while ($row = $result->fetch_assoc()) {
            $cat = $row['category'];
            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [];
            }
            $grouped[$cat][] = $row;
        }

        return $grouped;
    }

    /**
     * Get options by specific category
     */
    public function getByCategory(string $category, bool $onlyActive = true): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE category = ?";
        if ($onlyActive) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY display_order ASC, option_value ASC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];

        $stmt->bind_param("s", $category);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get all options for HR master data management screen
     */
    public function getAllForManagement(): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            ORDER BY category ASC, display_order ASC, id ASC
        ");

        if (!$stmt) return [];
        $stmt->execute();
        $result = $stmt->get_result();

        $grouped = [];
        while ($row = $result->fetch_assoc()) {
            $grouped[$row['category']][] = $row;
        }

        return $grouped;
    }

    /**
     * Create a new dropdown option
     */
    public function create(string $category, string $optionValue, int $displayOrder = 0, ?int $createdBy = null): int|bool
    {
        $category = trim($category);
        $optionValue = trim($optionValue);
        if (empty($category) || empty($optionValue)) return false;

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (category, option_value, display_order, is_active, created_by)
            VALUES (?, ?, ?, 1, ?)
            ON DUPLICATE KEY UPDATE is_active = 1, option_value = VALUES(option_value)
        ");

        if (!$stmt) return false;
        $stmt->bind_param("ssii", $category, $optionValue, $displayOrder, $createdBy);
        if ($stmt->execute()) {
            return $this->db->insert_id ?: true;
        }
        return false;
    }

    /**
     * Update an option value and order
     */
    public function update(int $id, string $optionValue, int $displayOrder = 0): bool
    {
        $optionValue = trim($optionValue);
        if (empty($optionValue)) return false;

        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET option_value = ?, display_order = ?
            WHERE id = ?
        ");

        if (!$stmt) return false;
        $stmt->bind_param("sii", $optionValue, $displayOrder, $id);
        return $stmt->execute();
    }

    /**
     * Toggle active/inactive status (retire or restore)
     */
    public function toggleStatus(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET is_active = IF(is_active = 1, 0, 1)
            WHERE id = ?
        ");

        if (!$stmt) return false;
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * If user selects 'Other' and enters a custom text, automatically ensure it exists in options
     */
    public function ensureOptionExists(string $category, string $optionValue, ?int $createdBy = null): void
    {
        $category = trim($category);
        $optionValue = trim($optionValue);
        if (empty($category) || empty($optionValue)) return;

        // Check if exists
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE category = ? AND LOWER(option_value) = LOWER(?) LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("ss", $category, $optionValue);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows == 0) {
                $this->create($category, $optionValue, 99, $createdBy);
            }
        }
    }
}
