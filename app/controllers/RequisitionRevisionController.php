<?php

require_once __DIR__ . '/../services/RequisitionRevisionService.php';

class RequisitionRevisionController extends Controller
{
    protected $db;
    protected $revisionService;

    public function __construct($db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user']['id'])) {
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }
        $this->revisionService = new RequisitionRevisionService($this->db);
    }

    /**
     * Central endpoint: Approver sends a requisition back to the Creator for Review / Edit
     * POST /requisition-revision/send-back
     */
    public function sendBack()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/home");
            exit;
        }

        $userId   = (int)($_SESSION['user']['id'] ?? 0);
        $roleId   = (int)($_SESSION['user']['role_id'] ?? 0);
        $userName = trim($_SESSION['user']['full_name'] ?? 'Approver');
        $roleName = trim($_SESSION['user']['role_name'] ?? '');
        $roleLc   = strtolower($roleName);

        $moduleTable      = trim($_POST['module_table'] ?? '');
        $sourceId         = (int)($_POST['source_id'] ?? 0);
        $returnStage      = trim($_POST['return_stage'] ?? 'approver');
        $returnStageLabel = trim($_POST['return_stage_label'] ?? ($roleName ?: 'Approver'));
        $reviewNote       = trim($_POST['review_note'] ?? '');
        $redirectUrl      = trim($_POST['redirect_url'] ?? '');

        $moduleConfig = [
            'fund_requisitions' => [
                'creator_col' => 'requested_by',
                'amount_col'  => 'amount',
                'default_url' => "/fund-requisitions/view/{$sourceId}",
                'final_statuses' => ['paid', 'rejected'],
                'status_col'  => 'status',
            ],
            'project_other_costs' => [
                'creator_col' => 'created_by_id',
                'amount_col'  => 'request_amount',
                'default_url' => "/project-other-costs/review/{$sourceId}",
                'final_statuses' => ['paid', 'rejected'],
                'status_col'  => 'status',
            ],
            'con_labor_requisitions' => [
                'creator_col' => 'requested_by',
                'amount_col'  => 'total_amount',
                'default_url' => "/con-labor-requisition/{$sourceId}",
                'final_statuses' => ['paid', 'labor_disbursed', 'rejected'],
                'status_col'  => 'status',
            ],
            'vendor_payment_requests' => [
                'creator_col' => 'created_by',
                'amount_col'  => 'invoice_amount',
                'default_url' => "/con-vendor-payment-show/{$sourceId}",
                'final_statuses' => ['Paid', 'Fully_Paid', 'Rejected'],
                'status_col'  => 'status',
            ],
            'contractor_bill_requests' => [
                'creator_col' => 'created_by',
                'amount_col'  => 'bill_amount',
                'default_url' => "/con-contractor-bill/{$sourceId}",
                'final_statuses' => ['paid', 'fully_paid', 'rejected'],
                'status_col'  => 'status',
            ],
            'purchase_requests' => [
                'creator_col' => 'created_by',
                'amount_col'  => null,
                'default_url' => "/con-purchase-request/{$sourceId}",
                'final_statuses' => ['Rejected'],
                'status_col'  => 'status',
            ],
            'purchase_orders' => [
                'creator_col' => 'created_by',
                'amount_col'  => null,
                'default_url' => "/con-purchase-order/{$sourceId}",
                'final_statuses' => ['Cancelled', 'Fully Received'],
                'status_col'  => 'status',
            ],
            'project_extra_assets' => [
                'creator_col' => 'requested_by',
                'amount_col'  => 'total_price',
                'default_url' => "/asset-extra-purchase/{$sourceId}",
                'final_statuses' => ['paid', 'purchased', 'rejected'],
                'status_col'  => 'overall_status',
            ],
        ];

        if (!isset($moduleConfig[$moduleTable]) || $sourceId <= 0) {
            setError('Invalid module or requisition ID.');
            header("Location: {$GLOBALS['base_url']}/home");
            exit;
        }

        $cfg = $moduleConfig[$moduleTable];
        $safeRedirect = (!empty($redirectUrl) && strpos($redirectUrl, '/') === 0)
            ? ($GLOBALS['base_url'] . $redirectUrl)
            : ($GLOBALS['base_url'] . $cfg['default_url']);

        // Block read-only observer roles
        if (in_array($roleId, [33, 34, 35], true) || in_array($roleLc, ['chief officer', 'management viewer', 'seal ceo'], true)) {
            setError('You do not have permission to send requests back for review.');
            header("Location: {$safeRedirect}");
            exit;
        }

        if ($reviewNote === '') {
            setError('Please provide a review / correction note before sending back to the creator.');
            header("Location: {$safeRedirect}");
            exit;
        }

        $res = $this->db->query("SELECT * FROM `{$moduleTable}` WHERE id = {$sourceId} LIMIT 1");
        $record = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        if (!$record) {
            setError('Requisition record not found.');
            header("Location: {$safeRedirect}");
            exit;
        }

        $currentStatus = (string)($record[$cfg['status_col']] ?? '');
        if (in_array($currentStatus, $cfg['final_statuses'], true)) {
            setError('Paid or rejected requisitions cannot be sent back for review.');
            header("Location: {$safeRedirect}");
            exit;
        }

        $creatorUserId = (int)($record[$cfg['creator_col']] ?? 0);
        $oldAmount     = (!empty($cfg['amount_col']) && isset($record[$cfg['amount_col']])) ? (float)$record[$cfg['amount_col']] : null;

        $ok = $this->revisionService->sendBackForReview(
            $moduleTable,
            $sourceId,
            $returnStage,
            $returnStageLabel,
            $userId,
            $userName,
            $roleName,
            $reviewNote,
            $creatorUserId,
            $oldAmount
        );

        if ($ok) {
            setSuccess("Sent back to the creator for review & correction. Once updated and resubmitted, it will return directly to {$returnStageLabel}.");
        } else {
            setError('Failed to send requisition back for review.');
        }

        header("Location: {$safeRedirect}");
        exit;
    }
}
