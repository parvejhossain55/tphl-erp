
-- 25/4/26
ALTER TABLE `leads` 
MODIFY COLUMN `lead_type` ENUM(
    'No Response', 'Pending', 'Prospect', 'High Prospect', 
    'Will Visit', 'Visited', 'Sold', 'Lead Transferred', 
    'Project Transferred', 'Not Interested', 'Don\'t Call Again', 
    'Refund', 'Future', 'Cold'
) DEFAULT 'Pending';

ALTER TABLE `lead_follow_ups` 
MODIFY COLUMN `follow_up_type` ENUM(
    'call', 'visit', 'meeting', 'whatsapp', 'email', 
    'Inbound Call', 'Phone Call', 'WA Call', 'Imo Call', 
    'Messenger Call', 'Messenger Conversesion', 'WA Conversesion', 
    'Imo Message', 'Phone Massaging', 'Email', 'Number Off', 
    'Wrong Number', 'Invalid Number', 'Face To Face'
) DEFAULT NULL;

-- নতুন কলাম যুক্ত করা
ALTER TABLE `lead_follow_ups`
ADD COLUMN `follow_up_agent_id` INT(11) NULL DEFAULT NULL AFTER `assigned_with`,
ADD COLUMN `next_follow_up_plan_notes` TEXT NULL AFTER `next_action`,
ADD COLUMN `lead_status` VARCHAR(50) NULL AFTER `discussion_note`;
