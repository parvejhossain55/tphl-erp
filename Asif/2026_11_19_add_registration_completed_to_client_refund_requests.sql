-- Add is_registration_completed flag to client_refund_requests
-- Date: 2026-11-19

ALTER TABLE client_refund_requests
    ADD COLUMN is_registration_completed TINYINT(1) NOT NULL DEFAULT 0 AFTER reason;
