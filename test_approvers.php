<?php
require 'app/config/config.php';
require 'app/core/Controller.php';
require 'app/controllers/HrmApiController.php';

$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$c = new HrmApiController($db);

// We will capture the JSON output of getTipsoiRequests
ob_start();
$c->getTipsoiRequests();
$json = ob_get_clean();

$data = json_decode($json, true);
$requests = $data['requests'] ?? [];

$approvers = [];

foreach ($requests as $req) {
    if ($req['status'] == 0) { // Pending
        if ($req['firstApprovalStatus'] == 0 && !empty($req['firstApproverId'])) {
            $approvers[$req['firstApproverId']] = $req['firstApproverName'];
        } elseif ($req['firstApprovalStatus'] == 1 && $req['secondApprovalStatus'] == 0 && !empty($req['secondApproverId'])) {
            $approvers[$req['secondApproverId']] = $req['secondApproverName'];
        }
    }
}

echo "Approvers needing to take action:\n";
print_r($approvers);
