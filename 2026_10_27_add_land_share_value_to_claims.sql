-- Add land_share_value to project_incentive_claims
ALTER TABLE project_incentive_claims ADD COLUMN land_share_value DECIMAL(15,2) DEFAULT 0.00 AFTER sale_amount;
