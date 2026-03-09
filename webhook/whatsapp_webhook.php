<?php
require __DIR__ . '../app/core/Database.php';
require __DIR__ . '/../app/Models/CampaignModel.php';

$db = new Database($db);
$campaignModel = new WhatsappCampaign($db->conn);

$payload = json_decode(file_get_contents('php://input'), true);

$lead_id = $payload['lead_id'];
$campaign_id = $payload['campaign_id'];
$status = $payload['status'];

if($status == 'delivered') {
    $campaignModel->markDelivered($campaign_id, $lead_id);
} elseif($status == 'success') {
    $campaignModel->markSuccess($campaign_id, $lead_id);
}

http_response_code(200);
echo json_encode(['status'=>'ok']);