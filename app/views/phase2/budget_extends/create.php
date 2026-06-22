<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Request Budget Adjustment';
?>
<div class="container-fluid">
    <!-- Breadcrumb & Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h4>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-budget-extends">Budget Adjustment Requests</a></li>
                <li class="breadcrumb-item active">New Request</li>
            </ol>
        </div>
    </div>

    <!-- Form Row -->
    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="card custom-card shadow-sm">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-patch-plus-fill me-2 text-primary"></i> Request Project Budget Adjustment
                    </h6>
                </div>
                <div class="card-body">
                    <form action="<?= $base_url ?>/con-budget-extend-store" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <!-- Project Select -->
                            <div class="col-md-12">
                                <label for="project_id" class="form-label fw-semibold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" id="project_id" class="form-select" required>
                                    <option value="">-- Select Project with Approved Budget --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>" data-cost="<?= (float)$p['total_estimated_cost'] ?>">
                                            <?= htmlspecialchars($p['name']) ?> (Approved Budget: ৳ <?= number_format($p['total_estimated_cost'], 2) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Dynamic Project Info Alert -->
                            <div class="col-12" id="projectBudgetInfo" style="display: none;">
                                <div class="alert alert-info py-3 mb-0">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi-info-circle-fill me-2 fs-16 text-primary"></i>
                                        <h6 class="mb-0 text-primary fw-semibold">Budget Summary & Adjustments</h6>
                                    </div>
                                    <div class="row g-2 text-dark" style="font-size: 13.5px;">
                                        <div class="col-sm-4">
                                            Original Approved Budget:<br>
                                            <span class="fw-bold fs-15 text-secondary">৳ <span id="lblBudgetCost">0.00</span></span>
                                        </div>
                                        <div class="col-sm-4">
                                            Proposed Adjustment:<br>
                                            <span class="fw-bold fs-15 text-warning" id="lblAdjustmentVal">৳ 0.00</span>
                                        </div>
                                        <div class="col-sm-4">
                                            Revised Total Budget:<br>
                                            <span class="fw-bold fs-15 text-success">৳ <span id="lblRevisedCost">0.00</span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Adjustment Type Select -->
                            <div class="col-md-4">
                                <label for="adjustment_type" class="form-label fw-semibold">Adjustment Type <span class="text-danger">*</span></label>
                                <select name="adjustment_type" id="adjustment_type" class="form-select" required>
                                    <option value="increase" selected>Increase (+)</option>
                                    <option value="decrease">Decrease (-)</option>
                                </select>
                            </div>

                            <!-- Amount -->
                            <div class="col-md-4">
                                <label for="amount" class="form-label fw-semibold">Adjustment Amount (৳) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">৳</span>
                                    <input type="number" name="amount" id="amount" class="form-control" 
                                           step="0.01" min="0.01" placeholder="0.00" required>
                                </div>
                            </div>

                            <!-- Subject / Title -->
                            <div class="col-md-4">
                                <label for="title" class="form-label fw-semibold">Adjustment Title / Subject <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="title" class="form-control" 
                                       placeholder="e.g., Additional foundation work cost" required>
                            </div>

                            <!-- Reason / Justification -->
                            <div class="col-md-12">
                                <label for="reason" class="form-label fw-semibold">Reason for Adjustment <span class="text-danger">*</span></label>
                                <textarea name="reason" id="reason" class="form-control" rows="4" 
                                          placeholder="Provide a detailed justification for why this adjustment is required..." required></textarea>
                            </div>

                            <!-- Google Drive link -->
                            <div class="col-md-6">
                                <label for="docs_link" class="form-label fw-semibold">Google Drive Document Link <span class="text-muted">(optional)</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-google"></i></span>
                                    <input type="url" name="docs_link" id="docs_link" class="form-control" 
                                           placeholder="https://drive.google.com/...">
                                </div>
                            </div>

                            <!-- Attachment file -->
                            <div class="col-md-6">
                                <label for="attachment_file" class="form-label fw-semibold">Upload Attachment <span class="text-muted">(optional - PDF/Image/Office docs)</span></label>
                                <input type="file" name="attachment_file" id="attachment_file" class="form-control" 
                                       accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx">
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="<?= $base_url ?>/con-budget-extends" class="btn btn-secondary btn-sm">
                                <i class="bi bi-x-circle me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-send me-1"></i> Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function formatCurrency(amount) {
    return parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function recalculateBudget() {
    const selectedOption = document.getElementById('project_id').options[document.getElementById('project_id').selectedIndex];
    const budgetInfoDiv = document.getElementById('projectBudgetInfo');
    const budgetCostSpan = document.getElementById('lblBudgetCost');
    const adjustmentValSpan = document.getElementById('lblAdjustmentVal');
    const revisedCostSpan = document.getElementById('lblRevisedCost');

    const amountInput = document.getElementById('amount');
    const adjustmentTypeSelect = document.getElementById('adjustment_type');

    if (selectedOption && selectedOption.value !== "") {
        const baseCost = parseFloat(selectedOption.getAttribute('data-cost') || 0);
        let adjAmount = parseFloat(amountInput.value || 0);
        const adjType = adjustmentTypeSelect.value;

        if (adjType === 'decrease') {
            adjAmount = -adjAmount;
        }

        const revisedCost = baseCost + adjAmount;

        // Update UI
        budgetCostSpan.textContent = formatCurrency(baseCost);
        
        if (adjAmount > 0) {
            adjustmentValSpan.className = "fw-bold fs-15 text-success";
            adjustmentValSpan.textContent = "+ ৳ " + formatCurrency(adjAmount);
        } else if (adjAmount < 0) {
            adjustmentValSpan.className = "fw-bold fs-15 text-danger";
            adjustmentValSpan.textContent = "- ৳ " + formatCurrency(Math.abs(adjAmount));
        } else {
            adjustmentValSpan.className = "fw-bold fs-15 text-warning";
            adjustmentValSpan.textContent = "৳ 0.00";
        }

        revisedCostSpan.textContent = formatCurrency(revisedCost);
        budgetInfoDiv.style.display = 'block';
    } else {
        budgetInfoDiv.style.display = 'none';
    }
}

document.getElementById('project_id').addEventListener('change', recalculateBudget);
document.getElementById('amount').addEventListener('input', recalculateBudget);
document.getElementById('adjustment_type').addEventListener('change', recalculateBudget);
</script>
