<?php
require_once __DIR__ . '/app/core/Controller.php';
require_once __DIR__ . '/app/controllers/EmployeeDashboardController.php';

// Mock DB and externalApiService
class MockDB {}
class MockExt {
    public function getBearerToken() {
        $config = require __DIR__ . '/app/config/config.php';
        $email = $config['tipsoi_email'];
        $password = $config['tipsoi_password'];
        $baseUrl = $config['tipsoi_base_url'];

        $loginUrl = $baseUrl . 'auth/external-sync/sign-in';
        $payload = json_encode(['email' => $email, 'password' => $password]);
        $ch = curl_init($loginUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: ' . strlen($payload)]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res, true)['token'] ?? null;
    }
}
class MockEDC extends EmployeeDashboardController {
    public function __construct() {
        $this->externalApiService = new MockExt();
    }
}

$_GET['page'] = 0;
$_GET['count'] = 100;
$_GET['status'] = 0;

$c = new MockEDC();
$c->getTipsoiPendingRequests();

