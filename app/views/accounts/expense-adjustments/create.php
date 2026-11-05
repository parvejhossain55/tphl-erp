<?php
$page_title = "Create Expense Adjustment";
$page = 'Accounts';
$show_breadcrumb = true;
?>
<div class="row">
    <div class="col-xxl-8 offset-xxl-2">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card__title">Submit Requisition Adjustment</h5>
                <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments" class="btn btn-secondary btn-sm">
                    <i class="fa-light fa-arrow-left me-1"></i> Back
                </a>
            </div>
            
            <div class="px-3 pt-2">
                <?php include __DIR__ . '/../../layouts/management_approval_notice.php'; ?>
            </div>
            <div class="card__body">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['error']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($userCashouts)): ?>
                    <div class="alert alert-warning">
                        You do not have any approved cashout requests to adjust. (Debug Role: <?= $_SESSION['user']['role_id'] ?? 'null' ?>, Count: <?= count($userCashouts ?? []) ?>)
                    </div>
                <?php else: ?>
                <?php 
                    $selectedParentType = $_GET['parent_type'] ?? '';
                    $selectedRequestId = (int)($_GET['cashout_request_id'] ?? 0);
                ?>
                <form action="<?= $GLOBALS['base_url'] ?>/expense-adjustments/store" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Select Original Cashout Request <span class="text-danger">*</span></label>
                        <select name="cashout_request_id" class="form-select" required>
                            <option value="">-- Select an approved request --</option>
                            <?php foreach ($userCashouts as $co): ?>
                                <?php 
                                    $prefix = 'REQ';
                                    if ($co['type'] == 'vendor') $prefix = 'VENDORREQ';
                                    if ($co['type'] == 'contractor') $prefix = 'CONTRACTORREQ';
                                    if ($co['type'] == 'purchase') $prefix = 'PURCHASEREQ';
                                    if ($co['type'] == 'other_cost') $prefix = 'OTHERCOST';
                                    if ($co['type'] == 'extra_purchase') $prefix = 'EXTRAPURCHASE';
                                    
                                    $isSelected = ($co['type'] == $selectedParentType && $co['id'] == $selectedRequestId);
                                ?>
                                <option value="<?= $co['type'] ?>:<?= $co['id'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                                    <?= $prefix ?>-<?= $co['id'] ?> - <?= htmlspecialchars($co['purpose']) ?> (TK.<?= number_format($co['amount'], 2) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">
                            <?php if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 6])): ?>
                                All approved requests are available for Accounts/Admins.
                            <?php else: ?>
                                Only your approved requests are shown here.
                            <?php endif; ?>
                        </small>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Adjustment Type <span class="text-danger">*</span></label>
                            <select name="adjustment_type" class="form-select" id="adjustmentType" required>
                                <option value="">-- Select Type --</option>
                                <option value="return">Return Unused Money (Cash-In)</option>
                                <option value="extra">Request Extra Money (Cash-Out)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount (TK.) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="1" required placeholder="0.00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Title / Purpose <span class="text-danger">*</span></label>
                        <?php
                        $defaultTitle = '';
                        if ($selectedParentType === 'vendor') $defaultTitle = 'Vendor Payment Settlement for VPR-' . $selectedRequestId;
                        elseif ($selectedParentType === 'contractor') $defaultTitle = 'Contractor Bill Settlement for CB-' . $selectedRequestId;
                        elseif ($selectedParentType === 'other_cost') $defaultTitle = 'Project Other Cost Settlement for REQ-' . $selectedRequestId;
                        elseif ($selectedParentType === 'extra_purchase') $defaultTitle = 'Office Procurement Settlement for REQ-' . $selectedRequestId;
                        ?>
                        <input type="text" name="title" class="form-control" required placeholder="Brief title for this adjustment" value="<?= htmlspecialchars($defaultTitle) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Provide detailed context..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <input type="text" name="note" class="form-control" placeholder="Short note or remark">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Documents (Optional)</label>
                        <input type="file" name="document[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        <small class="text-muted">Upload any receipts, approvals, or reference documents.</small>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-light fa-paper-plane me-2"></i>Submit Adjustment
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

