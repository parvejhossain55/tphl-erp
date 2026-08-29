<?php
require_once __DIR__ . '/app/core/Controller.php';
require_once __DIR__ . '/app/controllers/EmployeeDashboardController.php';

class MockDB {}
class MockExt {}
class MockEDC extends EmployeeDashboardController {
    public function __construct() {
        $this->externalApiService = new MockExt();
    }
    protected function getSessionEmployee() {
        return ['employee' => ['id' => 1]];
    }
}
$GLOBALS['base_url'] = 'http://localhost';
$c = new MockEDC();
$c->tipsoiPendingRequests();

