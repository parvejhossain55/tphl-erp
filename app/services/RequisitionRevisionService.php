<?php

class RequisitionRevisionService
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->ensureSchema();
    }

    public function ensureSchema(): void
    {
        static $checked = false;
        if ($checked || !$this->db) {
            return;
        }
        $checked = true;

        @$this->db->query("
            CREATE TABLE IF NOT EXISTS `requisition_revisions` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `module_table` VARCHAR(64) NOT NULL,
                `source_id` INT UNSIGNED NOT NULL,
                `status` ENUM('open', 'resubmitted', 'resolved') NOT NULL DEFAULT 'open',
                `return_stage` VARCHAR(50) NOT NULL,
                `return_stage_label` VARCHAR(100) NOT NULL,
                `requested_by` INT NOT NULL,
                `requested_by_name` VARCHAR(150) NULL DEFAULT NULL,
                `requested_by_role` VARCHAR(100) NULL DEFAULT NULL,
                `review_note` TEXT NOT NULL,
                `creator_user_id` INT NOT NULL DEFAULT 0,
                `old_amount` DECIMAL(15,2) NULL DEFAULT NULL,
                `new_amount` DECIMAL(15,2) NULL DEFAULT NULL,
                `resubmitted_at` DATETIME NULL DEFAULT NULL,
                `resubmitted_note` TEXT NULL DEFAULT NULL,
                `resolved_at` DATETIME NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_rev_mod_src_status` (`module_table`, `source_id`, `status`),
                KEY `idx_rev_creator_status` (`creator_user_id`, `status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function getOpenRevision(string $moduleTable, int $sourceId): ?array
    {
        if (!$this->db || $sourceId <= 0) {
            return null;
        }
        $stmt = $this->db->prepare("
            SELECT * FROM requisition_revisions
            WHERE module_table = ? AND source_id = ? AND status = 'open'
            ORDER BY id DESC LIMIT 1
        ");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('si', $moduleTable, $sourceId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function getLatestActiveOrResubmitted(string $moduleTable, int $sourceId): ?array
    {
        if (!$this->db || $sourceId <= 0) {
            return null;
        }
        $stmt = $this->db->prepare("
            SELECT * FROM requisition_revisions
            WHERE module_table = ? AND source_id = ? AND status IN ('open', 'resubmitted')
            ORDER BY id DESC LIMIT 1
        ");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('si', $moduleTable, $sourceId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function getHistory(string $moduleTable, int $sourceId): array
    {
        if (!$this->db || $sourceId <= 0) {
            return [];
        }
        $stmt = $this->db->prepare("
            SELECT * FROM requisition_revisions
            WHERE module_table = ? AND source_id = ?
            ORDER BY id DESC
        ");
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('si', $moduleTable, $sourceId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows ?: [];
    }

    public function getOpenRevisionMap(string $moduleTable): array
    {
        if (!$this->db) {
            return [];
        }
        $map = [];
        $stmt = $this->db->prepare("
            SELECT * FROM requisition_revisions
            WHERE module_table = ? AND status = 'open'
            ORDER BY id DESC
        ");
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('s', $moduleTable);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $sid = (int)$row['source_id'];
            if (!isset($map[$sid])) {
                $map[$sid] = $row;
            }
        }
        $stmt->close();
        return $map;
    }

    public function sendBackForReview(
        string $moduleTable,
        int $sourceId,
        string $returnStage,
        string $returnStageLabel,
        int $approverUserId,
        string $approverName,
        string $approverRole,
        string $reviewNote,
        int $creatorUserId,
        ?float $oldAmount = null
    ): bool {
        if (!$this->db || $sourceId <= 0 || trim($reviewNote) === '') {
            return false;
        }

        // Resolve any previously open/resubmitted rows before opening a new one
        $stmtClose = $this->db->prepare("
            UPDATE requisition_revisions
            SET status = 'resolved', resolved_at = NOW()
            WHERE module_table = ? AND source_id = ? AND status IN ('open', 'resubmitted')
        ");
        if ($stmtClose) {
            $stmtClose->bind_param('si', $moduleTable, $sourceId);
            $stmtClose->execute();
            $stmtClose->close();
        }

        $stmt = $this->db->prepare("
            INSERT INTO requisition_revisions
            (module_table, source_id, status, return_stage, return_stage_label, requested_by, requested_by_name, requested_by_role, review_note, creator_user_id, old_amount)
            VALUES (?, ?, 'open', ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param(
            'sississsid',
            $moduleTable,
            $sourceId,
            $returnStage,
            $returnStageLabel,
            $approverUserId,
            $approverName,
            $approverRole,
            $reviewNote,
            $creatorUserId,
            $oldAmount
        );
        $ok = $stmt->execute();
        $stmt->close();
        return (bool)$ok;
    }

    public function markResubmitted(string $moduleTable, int $sourceId, ?float $newAmount = null, ?string $resubmittedNote = null): bool
    {
        if (!$this->db || $sourceId <= 0) {
            return false;
        }
        $stmt = $this->db->prepare("
            UPDATE requisition_revisions
            SET status = 'resubmitted', new_amount = ?, resubmitted_note = ?, resubmitted_at = NOW(), updated_at = NOW()
            WHERE module_table = ? AND source_id = ? AND status = 'open'
        ");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('dssi', $newAmount, $resubmittedNote, $moduleTable, $sourceId);
        $ok = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $ok && $affected > 0;
    }

    public function markResolved(string $moduleTable, int $sourceId): void
    {
        if (!$this->db || $sourceId <= 0) {
            return;
        }
        $stmt = $this->db->prepare("
            UPDATE requisition_revisions
            SET status = 'resolved', resolved_at = NOW(), updated_at = NOW()
            WHERE module_table = ? AND source_id = ? AND status IN ('open', 'resubmitted')
        ");
        if ($stmt) {
            $stmt->bind_param('si', $moduleTable, $sourceId);
            $stmt->execute();
            $stmt->close();
        }
    }
}
