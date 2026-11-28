-- Reset legacy auto-filled penalty fields on non-penalty_settlement rows in lead_payments
-- Date: 2026-11-26
-- Reason: Prior to commit 151afb07, regular EMI cash-in vouchers auto-populated penalty_paid/penalty_amount.
--         Penalty collection is strictly tracked via payment_type = 'penalty_settlement'.

UPDATE lead_payments
SET
    penalty_amount = 0.00,
    penalty_paid   = 0.00,
    penalty_waived = 0.00,
    penalty_due    = 0.00
WHERE payment_type != 'penalty_settlement'
  AND (
      penalty_amount > 0
      OR penalty_paid > 0
      OR penalty_waived > 0
      OR penalty_due > 0
  );
