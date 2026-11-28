-- Migration: Central Requisition Revision / Send-Back-for-Review Workflow Table
-- Date: 2026-11-28
-- Purpose: Allows any stage approver to send a requisition back to the creator for review/edit
--          without losing earlier approvals, and routes it directly back to that approver on resubmit.

CREATE TABLE IF NOT EXISTS `requisition_revisions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `module_table` VARCHAR(64) NOT NULL,
    `source_id` INT UNSIGNED NOT NULL,
    `status` ENUM('open', 'resubmitted', 'resolved') NOT NULL DEFAULT 'open',
    `return_stage` VARCHAR(50) NOT NULL COMMENT 'Approver stage key (dept_head, pm, purchase_tl, purchase_head, qc, op_head, accounts_head, mgmt)',
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
    `revision_type` VARCHAR(30) NOT NULL DEFAULT 'review' COMMENT 'review = send back, rejection_appeal = creator resubmitted after rejection',
    `attachment_path` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Optional supporting proof uploaded on rejection appeal',
    `resolved_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rev_mod_src_status` (`module_table`, `source_id`, `status`),
    KEY `idx_rev_creator_status` (`creator_user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `requisition_edit_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `module_table` VARCHAR(64) NOT NULL,
    `source_id` INT UNSIGNED NOT NULL,
    `edited_by` INT NOT NULL,
    `edited_by_name` VARCHAR(150) NOT NULL,
    `edited_by_role` VARCHAR(100) NULL DEFAULT NULL,
    `change_summary` TEXT NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rel_mod_src` (`module_table`, `source_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
