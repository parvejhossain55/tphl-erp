-- Add 'distribute' column if not exists
ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `distribute` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 = Pending, 1 = Approved';

-- Set all existing leads to approved (1) so they remain active for sales teams
UPDATE `leads` SET `distribute` = 1 WHERE `distribute` = 0;

-- Helpful indexes for query speed
CREATE INDEX IF NOT EXISTS `idx_leads_distribute` ON `leads` (`distribute`);
CREATE INDEX IF NOT EXISTS `idx_leads_team_distribute` ON `leads` (`team_id`, `distribute`);
CREATE INDEX IF NOT EXISTS `idx_leads_phone_campaign` ON `leads` (`phone_number`, `campaign_name`(100));
