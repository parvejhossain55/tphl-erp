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
            'land_fund_requisitions' => [
                'creator_col' => 'requested_by',
                'amount_col'  => 'requested_amount',
                'default_url' => '/con-land-requisitions',
                'final_statuses' => ['Paid', 'Rejected'],
                'status_col'  => 'accounts_status',
            ],
            'cashout_requests' => [
                'creator_col' => 'requested_by',
                'amount_col'  => 'amount',
                'default_url' => "/view-cashout-request?id={$sourceId}",
                'final_statuses' => ['fully_paid', 'rejected'],
                'status_col'  => 'status',
            ],
            'vendors' => [
                'creator_col' => 'created_by',
                'amount_col'  => null,
                'default_url' => '/vendor-enrollment-requests',
                'final_statuses' => ['active', 'rejected'],
                'status_col'  => 'status',
            ],
            'contractors' => [
                'creator_col' => 'created_by',
                'amount_col'  => null,
                'default_url' => '/contractor-enrollment-requests',
                'final_statuses' => ['active', 'rejected'],
                'status_col'  => 'status',
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

    /**
     * Central endpoint: Creator (or Admin) resubmits a rejected requisition for re-review
     * with a mandatory valid cause/clarification and optional supporting attachment.
     * Restores the record to the exact stage where it was rejected while keeping all earlier approvals intact.
     * POST /requisition-revision/resubmit-rejected
     */
    public function resubmitRejected()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/home");
            exit;
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $isAdmin = in_array($roleId, [1, 3], true);

        $moduleTable = trim($_POST['module_table'] ?? '');
        $sourceId    = (int)($_POST['source_id'] ?? 0);
        $appealNote  = trim($_POST['appeal_note'] ?? '');
        $redirectUrl = trim($_POST['redirect_url'] ?? '');

        $moduleConfig = [
            'fund_requisitions'       => ['creator_col' => 'requested_by',  'amount_col' => 'amount',           'default_url' => "/fund-requisitions/view/{$sourceId}"],
            'project_other_costs'     => ['creator_col' => 'created_by_id', 'amount_col' => 'request_amount',   'default_url' => "/project-other-costs/review/{$sourceId}"],
            'con_labor_requisitions'  => ['creator_col' => 'requested_by',  'amount_col' => 'total_amount',     'default_url' => "/con-labor-requisition/{$sourceId}"],
            'vendor_payment_requests' => ['creator_col' => 'created_by',    'amount_col' => 'invoice_amount',   'default_url' => "/con-vendor-payment-show/{$sourceId}"],
            'contractor_bill_requests'=> ['creator_col' => 'created_by',    'amount_col' => 'bill_amount',      'default_url' => "/con-contractor-bill/{$sourceId}"],
            'purchase_requests'       => ['creator_col' => 'created_by',    'amount_col' => null,               'default_url' => "/con-purchase-request/{$sourceId}"],
            'purchase_orders'         => ['creator_col' => 'created_by',    'amount_col' => null,               'default_url' => "/con-purchase-order/{$sourceId}"],
            'project_extra_assets'    => ['creator_col' => 'requested_by',  'amount_col' => 'total_price',      'default_url' => "/asset-extra-purchase/{$sourceId}"],
            'land_fund_requisitions'  => ['creator_col' => 'requested_by',  'amount_col' => 'requested_amount', 'default_url' => '/con-land-requisitions'],
            'cashout_requests'        => ['creator_col' => 'requested_by',  'amount_col' => 'amount',           'default_url' => "/view-cashout-request?id={$sourceId}"],
            'vendors'                 => ['creator_col' => 'created_by',    'amount_col' => null,               'default_url' => '/vendor-enrollment-requests'],
            'contractors'             => ['creator_col' => 'created_by',    'amount_col' => null,               'default_url' => '/contractor-enrollment-requests'],
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

        if ($appealNote === '') {
            setError('Please provide a valid cause / clarification before resubmitting against rejection.');
            header("Location: {$safeRedirect}");
            exit;
        }

        $res = $this->db->query("SELECT * FROM `{$moduleTable}` WHERE id = {$sourceId} LIMIT 1");
        $rec = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        if (!$rec) {
            setError('Requisition record not found.');
            header("Location: {$safeRedirect}");
            exit;
        }

        $creatorUserId = (int)($rec[$cfg['creator_col']] ?? 0);
        if (!$isAdmin && $creatorUserId !== $userId) {
            setError('Only the creator or Admin can resubmit a rejected requisition for review.');
            header("Location: {$safeRedirect}");
            exit;
        }

        $rejInfo = $this->revisionService->getRejectionStatus($moduleTable, $sourceId);
        if (empty($rejInfo['is_rejected'])) {
            setError('This requisition is not currently rejected.');
            header("Location: {$safeRedirect}");
            exit;
        }

        // Optional supporting proof / attachment upload
        $attachmentPath = null;
        if (isset($_FILES['appeal_attachment']) && ($_FILES['appeal_attachment']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $tmpName  = $_FILES['appeal_attachment']['tmp_name'];
            $origName = $_FILES['appeal_attachment']['name'] ?? '';
            $fileSize = (int)($_FILES['appeal_attachment']['size'] ?? 0);
            $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx'];

            if (!in_array($ext, $allowedExt, true)) {
                setError('Unsupported attachment file type. Allowed: PDF, JPG, PNG, WEBP, DOC, DOCX, XLS, XLSX.');
                header("Location: {$safeRedirect}");
                exit;
            }
            if ($fileSize > 10 * 1024 * 1024) {
                setError('Attachment file size cannot exceed 10 MB.');
                header("Location: {$safeRedirect}");
                exit;
            }

            $uploadDir = dirname(dirname(__DIR__)) . '/public/uploads/revision_appeals/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }
            $fileName = 'appeal_' . $moduleTable . '_' . $sourceId . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
            if (@move_uploaded_file($tmpName, $uploadDir . $fileName)) {
                $attachmentPath = 'uploads/revision_appeals/' . $fileName;
            }
        }

        // 1. Apply any edited fields submitted inside the unified Edit & Resubmit modal
        $oldAmount = (!empty($cfg['amount_col']) && isset($rec[$cfg['amount_col']])) ? (float)$rec[$cfg['amount_col']] : null;
        $newAmount = $oldAmount;
        $editFieldsInput = $_POST['edit_fields'] ?? [];
        $fieldConfigs    = RequisitionRevisionService::getEditableFieldsConfig($moduleTable);

        if (is_array($editFieldsInput) && !empty($fieldConfigs)) {
            // Verify actual columns exist in the table to prevent SQL errors
            $existingCols = [];
            $colRes = @$this->db->query("SHOW COLUMNS FROM `{$moduleTable}`");
            if ($colRes) {
                while ($cr = $colRes->fetch_assoc()) {
                    $existingCols[$cr['Field']] = true;
                }
            }

            $setClauses = [];
            $types      = '';
            $vals       = [];
            $diffPairs  = [];

            foreach ($fieldConfigs as $col => $meta) {
                if (!isset($existingCols[$col]) || !array_key_exists($col, $editFieldsInput)) {
                    continue;
                }
                $rawOld = $rec[$col] ?? '';
                if (($meta['type'] ?? 'text') === 'number') {
                    $newVal = ($editFieldsInput[$col] === '' || $editFieldsInput[$col] === null) ? (float)$rawOld : (float)$editFieldsInput[$col];
                    if ($newVal < 0) $newVal = 0;
                    $setClauses[] = "`{$col}` = ?";
                    $types       .= 'd';
                    $vals[]       = $newVal;
                    $diffPairs[$meta['label']] = [(float)$rawOld, $newVal];
                    if (!empty($meta['is_amount'])) {
                        $newAmount = $newVal;
                    }
                } else {
                    $newVal = trim((string)$editFieldsInput[$col]);
                    if (($meta['type'] ?? '') === 'date' && $newVal === '') {
                        continue;
                    }
                    $setClauses[] = "`{$col}` = ?";
                    $types       .= 's';
                    $vals[]       = $newVal;
                    $diffPairs[$meta['label']] = [(string)$rawOld, $newVal];
                }
            }

            // Auto-recalculate derived totals for Labor Requisition & Office Procurement
            if ($moduleTable === 'con_labor_requisitions' && isset($existingCols['total_amount'])) {
                $lc = isset($editFieldsInput['labor_count']) ? max(1, (int)$editFieldsInput['labor_count']) : (int)($rec['labor_count'] ?? 1);
                $lr = isset($editFieldsInput['per_labor_rate']) ? max(0, (float)$editFieldsInput['per_labor_rate']) : (float)($rec['per_labor_rate'] ?? 0);
                $wd = isset($editFieldsInput['working_days']) ? max(1, (int)$editFieldsInput['working_days']) : (int)($rec['working_days'] ?? 1);
                $calcTotal = round($lc * $lr * $wd, 2);
                $setClauses[] = "`total_amount` = ?";
                $types       .= 'd';
                $vals[]       = $calcTotal;
                $diffPairs['Total Amount'] = [(float)($rec['total_amount'] ?? 0), $calcTotal];
                $newAmount = $calcTotal;
            } elseif ($moduleTable === 'project_extra_assets' && isset($existingCols['total_price'])) {
                $qty = isset($editFieldsInput['quantity']) ? max(1, (int)$editFieldsInput['quantity']) : (int)($rec['quantity'] ?? 1);
                $up  = isset($editFieldsInput['unit_price']) ? max(0, (float)$editFieldsInput['unit_price']) : (float)($rec['unit_price'] ?? 0);
                $calcTotal = round($qty * $up, 2);
                $setClauses[] = "`total_price` = ?";
                $types       .= 'd';
                $vals[]       = $calcTotal;
                $diffPairs['Total Price'] = [(float)($rec['total_price'] ?? 0), $calcTotal];
                $newAmount = $calcTotal;
            }

            if (!empty($setClauses)) {
                $sqlUp = "UPDATE `{$moduleTable}` SET " . implode(', ', $setClauses) . " WHERE id = ?";
                $types .= 'i';
                $vals[] = $sourceId;
                $stUp = $this->db->prepare($sqlUp);
                if ($stUp) {
                    $stUp->bind_param($types, ...$vals);
                    $stUp->execute();
                    $stUp->close();
                }

                // Sync approval_requests.request_amount if amount changed
                if ($newAmount !== null && $oldAmount !== null && abs($newAmount - $oldAmount) > 0.001) {
                    $arTables = [$moduleTable];
                    if ($moduleTable === 'land_fund_requisitions') $arTables[] = 'con_land_requisitions';
                    if ($moduleTable === 'contractor_bill_requests') $arTables[] = 'con_contractor_bills';
                    $inList = "'" . implode("','", $arTables) . "'";
                    $stAr = $this->db->prepare("UPDATE approval_requests SET request_amount = ? WHERE source_table IN ({$inList}) AND source_id = ?");
                    if ($stAr) {
                        $stAr->bind_param('di', $newAmount, $sourceId);
                        $stAr->execute();
                        $stAr->close();
                    }
                }

                // Log exact field-by-field diff if anything actually changed
                $fieldSummary = RequisitionRevisionService::buildChangeSummary($diffPairs);
                if ($fieldSummary !== '' && $fieldSummary !== 'Details updated') {
                    $this->revisionService->logEdit($moduleTable, $sourceId, $fieldSummary);
                }
            }
        }

        // 2. Restore the record to the exact stage where it was rejected (preserving earlier approvals)
        $restored = $this->restoreRejectedRecord($moduleTable, $sourceId, $rec, $rejInfo);
        if (!$restored) {
            setError('Failed to restore rejected requisition for re-review.');
            header("Location: {$safeRedirect}");
            exit;
        }

        $this->revisionService->recordRejectionAppeal(
            $moduleTable,
            $sourceId,
            $rejInfo['stage'] ?: 'approver',
            $rejInfo['stage_label'] ?: 'Approver',
            $creatorUserId,
            $rejInfo['reason'] ?? '',
            $appealNote,
            $attachmentPath,
            $oldAmount,
            $newAmount
        );

        setSuccess("Requisition updated & resubmitted for review" . ($attachmentPath ? " with supporting attachment" : "") . ". Returned directly to {$rejInfo['stage_label']}.");
        header("Location: {$safeRedirect}");
        exit;
    }

    /**
     * Restore a rejected record back to the exact stage where it was rejected,
     * keeping all previously approved stages intact and syncing approval_requests.
     */
    protected function restoreRejectedRecord(string $moduleTable, int $sourceId, array $rec, array $rejInfo): bool
    {
        $stage = $rejInfo['stage'] ?? '';

        // 1. Sync approval_requests if an entry exists
        $arTables = [$moduleTable];
        if ($moduleTable === 'land_fund_requisitions') $arTables[] = 'con_land_requisitions';
        if ($moduleTable === 'contractor_bill_requests') $arTables[] = 'con_contractor_bills';
        $inList = "'" . implode("','", $arTables) . "'";

        if ($stage === 'accounts_head') {
            $this->db->query("UPDATE approval_requests SET accounts_head_status = 'pending', status = 'pending', approve_role = 'accounts_head' WHERE source_table IN ({$inList}) AND source_id = {$sourceId}");
        } elseif ($stage === 'op_head') {
            $this->db->query("UPDATE approval_requests SET op_head_status = 'pending', status = 'pending', approve_role = 'operations' WHERE source_table IN ({$inList}) AND source_id = {$sourceId}");
        } else {
            $this->db->query("UPDATE approval_requests SET status = 'pending' WHERE source_table IN ({$inList}) AND source_id = {$sourceId} AND status = 'rejected'");
        }

        // 2. Restore the module table itself
        switch ($moduleTable) {
            case 'fund_requisitions':
                if (($rec['dept_head_status'] ?? '') === 'rejected') {
                    return (bool)$this->db->query("UPDATE fund_requisitions SET status = 'pending_dept', dept_head_status = 'pending' WHERE id = {$sourceId}");
                }
                if (empty($rec['qc_approved_by'])) {
                    return (bool)$this->db->query("UPDATE fund_requisitions SET status = 'pending_qc' WHERE id = {$sourceId}");
                }
                return (bool)$this->db->query("UPDATE fund_requisitions SET status = 'pending' WHERE id = {$sourceId}");

            case 'project_other_costs':
                if (($rec['dept_head_status'] ?? '') === 'rejected') {
                    return (bool)$this->db->query("UPDATE project_other_costs SET status = 'pending', dept_head_status = 'pending' WHERE id = {$sourceId}");
                }
                if (strtolower($rec['op_head_status'] ?? '') === 'rejected') {
                    return (bool)$this->db->query("UPDATE project_other_costs SET status = 'qc_approved', op_head_status = 'pending' WHERE id = {$sourceId}");
                }
                if (strtolower($rec['accounts_head_status'] ?? '') === 'rejected') {
                    return (bool)$this->db->query("UPDATE project_other_costs SET status = 'qc_approved', accounts_head_status = 'pending' WHERE id = {$sourceId}");
                }
                $newSt = !empty($rec['qc_audit_approved_id']) ? 'qc_approved' : 'pending';
                return (bool)$this->db->query("UPDATE project_other_costs SET status = '{$newSt}' WHERE id = {$sourceId}");

            case 'con_labor_requisitions':
                $newSt = empty($rec['pm_approved_by']) ? 'pending' : (empty($rec['qc_approved_by']) ? 'payment_requested' : 'qc_approved');
                return (bool)$this->db->query("UPDATE con_labor_requisitions SET status = '{$newSt}', rejection_reason = NULL WHERE id = {$sourceId}");

            case 'vendor_payment_requests':
                $newSt = !empty($rec['qc_approved_by_id']) ? 'QC Approved' : 'Submitted';
                return (bool)$this->db->query("UPDATE vendor_payment_requests SET status = '{$newSt}' WHERE id = {$sourceId}");

            case 'contractor_bill_requests':
                $newSt = !empty($rec['qc_approved_by_id']) ? 'qc_approved' : (!empty($rec['verified_by']) ? 'verified' : 'submitted');
                return (bool)$this->db->query("UPDATE contractor_bill_requests SET status = '{$newSt}', rejection_reason = NULL WHERE id = {$sourceId}");

            case 'purchase_requests':
                return (bool)$this->db->query("UPDATE purchase_requests SET status = 'Submitted' WHERE id = {$sourceId}");

            case 'purchase_orders':
                $newSt = !empty($rec['op_head_approved_by_id']) ? 'Operation Head Approved'
                    : (!empty($rec['qc_approved_by_id']) ? 'QC Approved'
                    : (!empty($rec['purchase_head_approved_by_id']) ? 'Purchase Head Approved' : 'Issued'));
                return (bool)$this->db->query("UPDATE purchase_orders SET status = '{$newSt}' WHERE id = {$sourceId}");

            case 'project_extra_assets':
                $sets = ["overall_status = 'in_review'"];
                foreach (['dept_head_status', 'it_head_status', 'operations_status', 'purchase_head_status', 'qc_audit_status', 'mgmt_status'] as $col) {
                    if (($rec[$col] ?? '') === 'rejected') {
                        $sets[] = "`{$col}` = 'pending'";
                        if ($col === 'dept_head_status') {
                            $sets[0] = "overall_status = 'pending'";
                        }
                    }
                }
                return (bool)$this->db->query("UPDATE project_extra_assets SET " . implode(', ', $sets) . " WHERE id = {$sourceId}");

            case 'land_fund_requisitions':
                $sets = [];
                if (($rec['mgmt_status'] ?? '') === 'Rejected') $sets[] = "mgmt_status = 'Pending'";
                if (($rec['accounts_status'] ?? '') === 'Rejected') $sets[] = "accounts_status = 'Pending'";
                if (empty($sets)) return true;
                return (bool)$this->db->query("UPDATE land_fund_requisitions SET " . implode(', ', $sets) . " WHERE id = {$sourceId}");

            case 'cashout_requests':
                $newSt = !empty($rec['qc_approved_by_id']) ? 'qc_approved' : 'pending';
                return (bool)$this->db->query("UPDATE cashout_requests SET status = '{$newSt}', rejected_by = NULL, rejected_at = NULL, rejection_reason = NULL WHERE id = {$sourceId}");

            case 'vendors':
            case 'contractors':
                $sets = ["status = 'pending_approval'", "rejection_reason = NULL"];
                foreach (['dept_head_status', 'purchase_head_status', 'role_20_status', 'role_38_status', 'management_status'] as $col) {
                    if (($rec[$col] ?? '') === 'rejected') {
                        $sets[] = "`{$col}` = 'pending'";
                    }
                }
                return (bool)$this->db->query("UPDATE `{$moduleTable}` SET " . implode(', ', $sets) . " WHERE id = {$sourceId}");
        }

        return false;
    }
}
