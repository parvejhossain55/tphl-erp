<div class="pagetitle d-flex justify-content-between align-items-center">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-vendor-penalties">Vendor Penalties</a></li>
                <li class="breadcrumb-item active">Issue Penalty</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-vendor-penalties" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-exclamation-triangle me-2 text-warning"></i>Issue Manual Vendor Penalty
                    </h6>
                </div>
                <div class="card__body p-4">
                    <div class="alert alert-info small mb-4">
                        <i class="bi bi-info-circle me-1"></i>
                        Note: QC rejections auto-create penalties. Use this form for <strong>manual</strong> penalties
                        (late delivery, shortage, etc.). Vendor rating is recalculated automatically.
                    </div>

                    <form action="<?= $base_url ?>/con-vendor-penalty-store" method="POST">

                        <div class="row ">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" class="form-select select2" required>
                                    <option value="">-- Select Vendor --</option>
                                    <?php foreach ($vendors as $v): ?>
                                        <?php
                                        $vComp = trim($v['company_name'] ?? '');
                                        $vName = trim($v['name'] ?? '');
                                        if (!empty($vComp) && !empty($vName) && strcasecmp($vComp, $vName) !== 0) {
                                            $vLabel = $vComp . ' (' . $vName . ')';
                                        } else {
                                            $vLabel = !empty($vComp) ? $vComp : $vName;
                                        }
                                        ?>
                                        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($vLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Penalty Category <span class="text-danger">*</span></label>
                                <select name="penalty_category" class="form-select" required>
                                    <option value="">-- Select Category --</option>
                                    <option value="Late Delivery">Late Delivery</option>
                                    <option value="Quality Issue">Quality Issue</option>
                                    <option value="Shortage">Shortage</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                        </div>

                        <div class="row  mt-2">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Penalty Amount (৳)</label>
                                <input type="number" step="0.01" name="penalty_amount" class="form-control"
                                       min="0" value="0" placeholder="0.00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Action Taken</label>
                                <select name="action_taken" class="form-select">
                                    <option value="Deduct from Bill">Deduct from Bill</option>
                                    <option value="Replacement Taken">Replacement Taken</option>
                                    <option value="Fine Paid">Fine Paid</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">PO Reference</label>
                                <input type="number" name="purchase_order_id" class="form-control"
                                       min="0" value="0" placeholder="PO ID (optional)">
                            </div>
                        </div>

                        <div class="row  mt-2">
                            <div class="col-12">
                                <label class="form-label fw-bold">Reason / Description <span class="text-danger">*</span></label>
                                <textarea name="reason" class="form-control" rows="3" required
                                          placeholder="Describe why this penalty is being issued..."></textarea>
                            </div>
                        </div>

                        <div class="mt-5 text-end">
                            <a href="<?= $base_url ?>/con-vendor-penalties" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-danger px-4 fw-bold"
                                    onclick="return confirm('Issue this penalty? Vendor rating will be affected.');">
                                <i class="bi bi-exclamation-triangle me-1"></i> Issue Penalty
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
