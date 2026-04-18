<div class="pagetitle d-flex justify-content-between align-items-center">
    <div>
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-budget-extends">Budget Extensions</a></li>
                <li class="breadcrumb-item active">New Request</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-budget-extends" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-coin me-2"></i>Request Budget Extension
                    </h6>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-budget-extend-store" method="POST">

                        <div class="row  mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" class="form-select" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Initial Budget Reference <span class="text-danger">*</span>
                                </label>
                                <select name="initial_budget_id" class="form-select" required>
                                    <option value="">-- Select Budget --</option>
                                    <?php foreach ($budgets as $b): ?>
                                        <option value="<?= $b['id'] ?>">
                                            #<?= $b['id'] ?> — <?= htmlspecialchars($b['project_name']) ?>
                                            (৳<?= number_format($b['total_estimated_cost'], 0) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Subject / Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="title" class="form-control"
                                   placeholder="e.g. Additional Piling Work, Extra Foundation Cost"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Extension Amount (৳) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" step="0.01" name="amount"
                                       class="form-control" placeholder="0.00" min="0.01" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                Reason / Justification <span class="text-danger">*</span>
                            </label>
                            <textarea name="reason" class="form-control" rows="4" required
                                      placeholder="Explain why the additional budget is needed…"></textarea>
                        </div>

                        <div class="alert alert-info py-2 small mb-4">
                            <i class="bi bi-info-circle me-1"></i>
                            Once submitted, this request will be reviewed by management. Approved extensions
                            automatically increase the project's Revised Total Budget.
                        </div>

                        <div class="text-end">
                            <a href="<?= $base_url ?>/con-budget-extends" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-warning px-4 fw-bold">
                                <i class="bi bi-send me-1"></i> Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
