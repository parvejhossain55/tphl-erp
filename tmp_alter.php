<?php
$db = new mysqli("localhost", "root", "", "tphl_db");

$queries = [
    "ALTER TABLE `lead_follow_ups` ADD COLUMN `next_follow_up_plan_notes` TEXT NULL AFTER `next_action`",
    "ALTER TABLE `lead_follow_ups` ADD COLUMN `lead_status` VARCHAR(50) NULL AFTER `discussion_note`"
];

foreach ($queries as $query) {
    if ($db->query($query)) {
        echo "Success: " . substr($query, 0, 50) . "...\n";
    } else {
        echo "Error: " . $db->error . " \nQuery: " . $query . "\n";
    }
}
