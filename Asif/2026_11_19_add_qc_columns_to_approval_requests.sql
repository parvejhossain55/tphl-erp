-- Add QC approval stage columns to approval_requests
-- Date: 2026-11-19

ALTER TABLE approval_requests
    ADD COLUMN qc_status VARCHAR(50) NULL DEFAULT 'pending' AFTER sales_head_note,
    ADD COLUMN qc_by INT NULL DEFAULT NULL AFTER qc_status,
    ADD COLUMN qc_at DATETIME NULL DEFAULT NULL AFTER qc_by,
    ADD COLUMN qc_note TEXT NULL AFTER qc_at;
