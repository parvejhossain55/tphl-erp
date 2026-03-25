<?php

require __DIR__ . '/../app/core/Database.php';
require __DIR__ . '/../app/models/WhatsappCampaign.php';

$config = require __DIR__ . '/../app/config/config.php';

$db = new Database($config);
$campaignModel = new WhatsappCampaign($db->conn);

echo "Cron Started\n";

$campaigns = $campaignModel->getScheduledCampaigns();

echo "Campaign Found: " . count($campaigns) . "\n";

foreach ($campaigns as $campaign) {

    echo "Checking Campaign ID: " . $campaign['id'] . "\n";

    // STEP 1: Queue create
    $campaignModel->createQueue($campaign);

    // STEP 2: Pending messages
    $messages = $campaignModel->getPendingMessages($campaign['id']);

    echo "Pending Messages: " . count($messages) . "\n";

    foreach ($messages as $msg) {

        echo "Sending message to Lead ID: {$msg['lead_id']}\n";

        // WhatsApp API call here

        $campaignModel->markSent($msg['campaign_id'], $msg['lead_id']);
    }
}